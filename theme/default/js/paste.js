function getElementsByClassName(e, t) {
    if (e.getElementsByClassName) {
        return e.getElementsByClassName(t);
    } else {
        return function n(e, t) {
            if (t == null) t = document;
            var n = [], r = t.getElementsByTagName("*"), i = r.length, s = new RegExp("(^|\\s)" + e + "(\\s|$)"), o, u;
            for (o = 0, u = 0; o < i; o++) {
                if (s.test(r[o].className)) { n[u] = r[o]; u++; }
            }
            return n;
        }(t, e);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('paste.js loaded at', new Date().toISOString());

    // Check if CodeMirror is loaded
    if (typeof CodeMirror === 'undefined') {
        console.error('CodeMirror library not loaded');
        return;
    }

    // Initialize CodeMirror for #paste_data (main.php, editable)
    const pasteDataTextArea = document.getElementById('paste_data');
    if (pasteDataTextArea && !pasteDataTextArea.classList.contains('cm-initialized')) {
        console.log('Initializing CodeMirror for #paste_data');
        try {
            const pasteDataEditor = CodeMirror.fromTextArea(pasteDataTextArea, {
                mode: 'markdown',
                theme: 'monokai',
                lineNumbers: true,
                readOnly: false
            });
            pasteDataTextArea.classList.add('cm-initialized');
            console.log('CodeMirror initialized for #paste_data');
            pasteDataEditor.on('change', () => {
                console.log('paste_data editor content changed');
            });
        } catch (e) {
            console.error('Failed to initialize CodeMirror for #paste_data:', e);
        }
    } else if (pasteDataTextArea) {
        console.log('paste_data already initialized');
    } else {
        console.log('paste_data textarea not found');
    }

    // Initialize CodeMirror for #code (view.php, read-only)
    const codeTextArea = document.getElementById('code');
    if (codeTextArea && !codeTextArea.classList.contains('cm-initialized')) {
        console.log('Initializing CodeMirror for #code');
        try {
            CodeMirror.fromTextArea(codeTextArea, {
                mode: 'markdown',
                theme: 'monokai',
                lineNumbers: true,
                readOnly: true
            });
            codeTextArea.classList.add('cm-initialized');
            console.log('CodeMirror initialized for #code');
        } catch (e) {
            console.error('Failed to initialize CodeMirror for #code:', e);
        }
    }

    // Initialize CodeMirror for #edit-code (view.php, editable)
    const editCodeTextArea = document.getElementById('edit-code');
    if (editCodeTextArea && !editCodeTextArea.classList.contains('cm-initialized')) {
        console.log('Initializing CodeMirror for #edit-code');
        try {
            const editCodeEditor = CodeMirror.fromTextArea(editCodeTextArea, {
                mode: 'markdown',
                theme: 'monokai',
                lineNumbers: true,
                readOnly: false
            });
            editCodeTextArea.classList.add('cm-initialized');
            console.log('CodeMirror initialized for #edit-code');
        } catch (e) {
            console.error('Failed to initialize CodeMirror for #edit-code:', e);
        }
    }

    // Function to show notification with delay
    function showNotification(message, isError = false) {
        console.log('Attempting to show notification:', message);
        setTimeout(() => {
            const notification = document.getElementById('notification');
            if (notification) {
                console.log('Notification element found, displaying message');
                notification.textContent = message;
                notification.className = 'notification' + (isError ? ' error' : '');
                notification.style.display = 'block';
                setTimeout(() => {
                    console.log('Fading out notification');
                    notification.classList.add('fade-out');
                    setTimeout(() => {
                        console.log('Hiding notification');
                        notification.style.display = 'none';
                        notification.classList.remove('fade-out');
                        notification.textContent = '';
                    }, 500); // Match CSS fade-out duration
                }, 10000); // Show for 10 seconds
            } else {
                console.error('Notification element not found');
            }
        }, 100); // Delay to ensure DOM readiness
    }

    // Toggle line numbers (GeSHi only, view.php)
    window.togglev = function() {
        console.log('Toggling line numbers for GeSHi in view.php');
        const olElement = document.getElementsByTagName("ol")[0];
        if (olElement) {
            const currentStyle = olElement.style.listStyle || getComputedStyle(olElement).listStyle;
            console.log('Current list-style:', currentStyle);
            if (currentStyle.substr(0, 4) == "none") {
                olElement.style.listStyle = "decimal";
                console.log('Set list-style to decimal on first ol');
            } else {
                olElement.style.listStyle = "none";
                console.log('Set list-style to none on first ol');
            }
        } else {
            console.error('ol element not found in document');
            showNotification('Error: Code list element not found.', true);
        }
    };

    // Toggle full screen
    window.toggleFullScreen = function() {
        console.log('Toggling full screen');
        const modalElement = document.getElementById('fullscreenModal');
        if (modalElement) {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalElement);
            bsModal.show();
            modalElement.addEventListener('hidden.bs.modal', function handler() {
                console.log('Fullscreen modal hidden, removing backdrop');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                document.body.classList.remove('modal-open');
                modalElement.removeEventListener('hidden.bs.modal', handler);
            }, { once: true });
        } else {
            console.error('fullscreenModal not found');
            showNotification('Error: Fullscreen modal not available.', true);
        }
    };

    // Copy to clipboard
    window.copyToClipboard = function() {
        console.log('Copy to Clipboard button clicked in copyToClipboard');
        const codeText = document.getElementById('code').value;
        if (!codeText) {
            console.error('No text found in #code');
            showNotification('Error: No code to copy.', true);
            return;
        }
        navigator.clipboard.writeText(codeText).then(() => {
            console.log('Successfully copied to clipboard');
            showNotification('Copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy:', err);
            showNotification('Failed to copy.', true);
        });
    };

    // Show embed code
    window.showEmbedCode = function() {
        console.log('Embed Tool button clicked in showEmbedCode');
        const urlParams = new URLSearchParams(window.location.search);
        const pasteId = urlParams.get('id');
        if (pasteId) {
            const basePath = window.location.pathname.includes('view.php') ? '/embed.php?id=' : '/embed/';
            const embedCode = `<iframe src="${window.location.origin}${basePath}${pasteId}" width="100%" height="400px" frameborder="0"></iframe>`;
            console.log('Generated embed code:', embedCode);
            showNotification(`Embed code: ${embedCode}`);
        } else {
            console.error('Paste ID not found in URL');
            showNotification('Error: Could not generate embed code. Paste ID missing.', true);
        }
    };

    // Highlight line
    window.highlightLine = function(event) {
        console.log('Attempting to highlight selected lines');
        const editCodeTextArea = document.getElementById('edit-code');
        if (editCodeTextArea && editCodeTextArea.classList.contains('cm-initialized')) {
            const cmInstance = editCodeTextArea.nextSibling.CodeMirror;
            if (cmInstance) {
                console.log('Adding !highlight! to selected lines in #edit-code');
                cmInstance.operation(() => {
                    const selections = cmInstance.getSelections();
                    const ranges = cmInstance.listSelections();
                    for (let i = 0; i < selections.length; i++) {
                        const lines = selections[i].split('\n');
                        const modifiedLines = lines.map(line => line.startsWith('!highlight!') ? line : `!highlight!${line}`);
                        cmInstance.replaceSelection(modifiedLines.join('\n'), ranges[i]);
                    }
                });
                cmInstance.focus();
            } else {
                console.error('CodeMirror instance not found for #edit-code');
                showNotification('Error: CodeMirror editor not initialized.', true);
            }
        } else {
            console.error('edit-code textarea not found or not initialized');
            showNotification('Error: Edit mode not available.', true);
        }
    };
});
