/**
 * Nextcloud Secure Office - Admin & Information Owner Management UI
 * Compliant with Nextcloud CSP (no inline scripts)
 */

(function() {
    'use strict';

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    function toggleTargetTypeFields(val) {
        const userBox = document.getElementById('sec_target_user_box');
        const groupBox = document.getElementById('sec_target_group_box');
        if (!userBox || !groupBox) return;
        if (val === 'user') {
            userBox.style.display = 'block';
            groupBox.style.display = 'none';
        } else if (val === 'group') {
            userBox.style.display = 'none';
            groupBox.style.display = 'block';
        } else {
            userBox.style.display = 'none';
            groupBox.style.display = 'none';
        }
    }

    let searchTimeout = null;
    function debounceFileSearch(val) {
        clearTimeout(searchTimeout);
        const resultsBox = document.getElementById('sec_file_search_results');
        if (!resultsBox) return;

        if (!val || val.trim().length < 2) {
            resultsBox.style.display = 'none';
            resultsBox.innerHTML = '';
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(OC.generateUrl('/apps/secure_office/api/v1/files/search') + '?q=' + encodeURIComponent(val.trim()), {
                headers: { 'requesttoken': OC.requestToken }
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.status === 'success' && data.files && data.files.length > 0) {
                    resultsBox.innerHTML = '';
                    data.files.forEach(f => {
                        const item = document.createElement('div');
                        item.style.padding = '8px 12px';
                        item.style.cursor = 'pointer';
                        item.style.borderBottom = '1px solid #f1f5f9';
                        item.style.fontSize = '0.85em';
                        item.innerHTML = '<strong>' + escapeHtml(f.name) + '</strong> <span style="color:#64748b; font-size:0.8em;">(ID: ' + f.fileid + ' - ' + escapeHtml(f.path) + ')</span>';
                        item.onmouseover = () => { item.style.background = '#e0f2fe'; };
                        item.onmouseout = () => { item.style.background = '#fff'; };
                        item.onclick = () => {
                            document.getElementById('sec_rule_file_id').value = f.fileid;
                            document.getElementById('sec_rule_file_name').value = f.name;
                            document.getElementById('sec_rule_file_path').value = f.path;
                            resultsBox.style.display = 'none';
                        };
                        resultsBox.appendChild(item);
                    });
                    resultsBox.style.display = 'block';
                } else {
                    resultsBox.innerHTML = '<div style="padding:8px 12px; color:#64748b; font-size:0.85em; font-style:italic;">No se encontraron archivos en oc_filecache</div>';
                    resultsBox.style.display = 'block';
                }
            })
            .catch(() => {
                resultsBox.style.display = 'none';
            });
        }, 250);
    }

    function saveSecureOfficeSettings(event) {
        if (event) event.preventDefault();
        const btn = document.getElementById('btn-save-settings');
        const msg = document.getElementById('save-status-msg');
        if (!btn || !msg) return false;

        btn.disabled = true;
        msg.style.color = '#0082c9';
        msg.innerText = (typeof t === 'function') ? t('secure_office', 'Saving policies...') : 'Guardando directivas...';

        const getCheckedGroups = (name) => {
            const checkboxes = document.querySelectorAll('input[name="' + name + '[]"]:checked');
            return Array.from(checkboxes).map(cb => cb.value);
        };

        const payload = {
            ens_classification: document.getElementById('sec_classification').value,
            delegated_admin_groups: getCheckedGroups('delegated_admin_groups'),
            collabora_protection_enabled: document.getElementById('sec_collabora_protection_enabled').checked,
            watermark_enabled: document.getElementById('sec_watermark_enabled').checked,
            watermark_template: document.getElementById('sec_watermark_template').value,
            dlp_disable_export: document.getElementById('sec_dlp_disable_export').checked,
            dlp_export_allowed_groups: getCheckedGroups('dlp_export_allowed_groups'),
            dlp_disable_copy: document.getElementById('sec_dlp_disable_copy').checked,
            dlp_copy_allowed_groups: getCheckedGroups('dlp_copy_allowed_groups'),
            dlp_disable_print: document.getElementById('sec_dlp_disable_print').checked,
            dlp_print_allowed_groups: getCheckedGroups('dlp_print_allowed_groups'),
            native_protection_enabled: document.getElementById('sec_native_protection_enabled').checked,
            native_audit_enabled: document.getElementById('sec_native_audit_enabled').checked,
            native_dlp_disable_download: document.getElementById('sec_native_dlp_disable_download').checked,
            native_dlp_allowed_groups: getCheckedGroups('native_dlp_allowed_groups')
        };

        fetch(OC.generateUrl('/apps/secure_office/api/v1/settings'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data && data.status === 'success') {
                msg.style.color = '#28a745';
                msg.innerText = '✓ ' + data.message;
                setTimeout(() => { msg.innerText = ''; }, 4000);
            } else {
                msg.style.color = '#dc3545';
                msg.innerText = (data && data.message) ? data.message : 'Error al guardar configuración.';
            }
        })
        .catch(err => {
            btn.disabled = false;
            msg.style.color = '#dc3545';
            msg.innerText = 'Error de conexión con el servidor.';
            console.error(err);
        });

        return false;
    }

    function saveGranularFileRule() {
        const fileId = parseInt(document.getElementById('sec_rule_file_id').value, 10);
        const fileName = document.getElementById('sec_rule_file_name').value;
        const filePath = document.getElementById('sec_rule_file_path').value;
        const targetType = document.getElementById('sec_rule_target_type').value;
        let targetId = '*';
        if (targetType === 'user') {
            targetId = document.getElementById('sec_rule_user_id').value.trim();
            if (!targetId) {
                alert('Por favor, indique el identificador del usuario.');
                return;
            }
        } else if (targetType === 'group') {
            targetId = document.getElementById('sec_rule_group_id').value;
            if (!targetId) {
                alert('Por favor, seleccione un grupo.');
                return;
            }
        }

        if (!fileId || isNaN(fileId) || fileId <= 0) {
            alert('Por favor, indique un ID de archivo válido.');
            return;
        }

        const payload = {
            file_id: fileId,
            file_name: fileName || 'document',
            file_path: filePath,
            target_type: targetType,
            target_id: targetId,
            dlp_export: parseInt(document.getElementById('sec_rule_dlp_export').value, 10),
            dlp_print: parseInt(document.getElementById('sec_rule_dlp_print').value, 10),
            dlp_copy: parseInt(document.getElementById('sec_rule_dlp_copy').value, 10),
            dlp_download: parseInt(document.getElementById('sec_rule_dlp_download').value, 10),
            classification: document.getElementById('sec_rule_classification').value.trim()
        };

        const btn = document.getElementById('btn-save-file-rule');
        const msg = document.getElementById('file-rule-status-msg');
        btn.disabled = true;
        msg.style.color = '#0082c9';
        msg.innerText = (typeof t === 'function') ? t('secure_office', 'Saving rule...') : 'Guardando regla...';

        fetch(OC.generateUrl('/apps/secure_office/api/v1/file-rules'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data && data.status === 'success') {
                msg.style.color = '#28a745';
                msg.innerText = '✓ ' + data.message;
                setTimeout(() => { msg.innerText = ''; }, 3000);
                renderFileRulesTable(data.rules);
            } else {
                msg.style.color = '#dc3545';
                msg.innerText = (data && data.message) ? data.message : 'Error al guardar regla.';
            }
        })
        .catch(err => {
            btn.disabled = false;
            msg.style.color = '#dc3545';
            msg.innerText = 'Error de conexión con el servidor.';
            console.error(err);
        });
    }

    function deleteGranularFileRule(ruleId) {
        if (!confirm('¿Desea eliminar esta regla de archivo?')) {
            return;
        }

        fetch(OC.generateUrl('/apps/secure_office/api/v1/file-rules/' + ruleId), {
            method: 'DELETE',
            headers: {
                'requesttoken': OC.requestToken
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.status === 'success') {
                renderFileRulesTable(data.rules);
            } else {
                alert((data && data.message) ? data.message : 'Error al eliminar regla.');
            }
        })
        .catch(err => {
            alert('Error de conexión con el servidor.');
            console.error(err);
        });
    }

    function renderFileRulesTable(rules) {
        const tbody = document.getElementById('file-rules-tbody');
        const countBadge = document.getElementById('file-rules-count');
        if (countBadge) {
            countBadge.innerText = rules.length;
        }

        if (!tbody) return;

        if (!rules || rules.length === 0) {
            tbody.innerHTML = '<tr id="no-file-rules-row"><td colspan="8" style="padding:12px; text-align:center; color:#64748b; font-style:italic;">No hay reglas específicas configuradas. Aplican las políticas globales.</td></tr>';
            return;
        }

        const renderPermBadge = (val) => {
            val = parseInt(val, 10);
            if (val === 1) return '<span style="color:#16a34a; font-weight:bold;">✓ Permitir</span>';
            if (val === -1) return '<span style="color:#dc2626; font-weight:bold;">⛔ Bloquear</span>';
            return '<span style="color:#64748b;">Heredar</span>';
        };

        let html = '';
        rules.forEach(r => {
            let badgeTarget = '🌐 Todos (*)';
            if (r.target_type === 'user') {
                badgeTarget = '👤 ' + escapeHtml(r.target_id);
            } else if (r.target_type === 'group') {
                badgeTarget = '👥 ' + escapeHtml(r.target_id);
            }

            html += '<tr style="border-bottom:1px solid #e2e8f0;" id="file-rule-row-' + r.id + '">';
            html += '<td style="padding:8px 10px;"><strong>#' + r.file_id + '</strong> ' + escapeHtml(r.file_name || 'document') + '</td>';
            html += '<td style="padding:8px 10px;"><span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:10px; font-size:0.85em; font-weight:600;">' + badgeTarget + '</span></td>';
            html += '<td style="padding:8px 10px; text-align:center;">' + renderPermBadge(r.dlp_export) + '</td>';
            html += '<td style="padding:8px 10px; text-align:center;">' + renderPermBadge(r.dlp_print) + '</td>';
            html += '<td style="padding:8px 10px; text-align:center;">' + renderPermBadge(r.dlp_copy) + '</td>';
            html += '<td style="padding:8px 10px; text-align:center;">' + renderPermBadge(r.dlp_download) + '</td>';
            html += '<td style="padding:8px 10px;"><span style="font-size:0.85em; color:#475569;">' + escapeHtml(r.classification || '(Heredada)') + '</span></td>';
            html += '<td style="padding:8px 10px; text-align:center;"><button type="button" class="button delete-rule-btn" data-rule-id="' + r.id + '" style="color:#dc2626; padding:2px 6px; font-size:0.8em;">🗑️ Eliminar</button></td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;

        tbody.querySelectorAll('.delete-rule-btn').forEach(btn => {
            btn.onclick = () => deleteGranularFileRule(btn.getAttribute('data-rule-id'));
        });
    }

    // Attach listeners on DOM ready
    function init() {
        const searchInput = document.getElementById('sec_rule_file_search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => debounceFileSearch(e.target.value));
        }

        const targetTypeSelect = document.getElementById('sec_rule_target_type');
        if (targetTypeSelect) {
            targetTypeSelect.addEventListener('change', (e) => toggleTargetTypeFields(e.target.value));
        }

        const saveSettingsBtn = document.getElementById('btn-save-settings');
        if (saveSettingsBtn) {
            saveSettingsBtn.addEventListener('click', saveSecureOfficeSettings);
        }

        const saveFileRuleBtn = document.getElementById('btn-save-file-rule');
        if (saveFileRuleBtn) {
            saveFileRuleBtn.addEventListener('click', saveGranularFileRule);
        }

        document.querySelectorAll('.delete-rule-btn').forEach(btn => {
            btn.onclick = () => deleteGranularFileRule(btn.getAttribute('data-rule-id'));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose helpers globally on window.SecureOffice for debugging / automated tests
    window.SecureOffice = {
        debounceFileSearch: debounceFileSearch,
        toggleTargetTypeFields: toggleTargetTypeFields,
        saveSecureOfficeSettings: saveSecureOfficeSettings,
        saveGranularFileRule: saveGranularFileRule,
        deleteGranularFileRule: deleteGranularFileRule,
        renderFileRulesTable: renderFileRulesTable
    };
})();
