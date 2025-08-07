document.addEventListener('DOMContentLoaded', () => {
  // Card Tools
  const setupCardTools = () => {
    // Minimize Card
    document.querySelectorAll('.card-tools .minimise-tool').forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const card = button.closest('.card');
        const cardBody = card.querySelector('.card-body');
        if (cardBody) {
          cardBody.style.transition = 'height 0.1s ease';
          cardBody.style.height = cardBody.style.height === '0px' ? `${cardBody.scrollHeight}px` : '0px';
        }
      });
    });

    // Close Card
    document.querySelectorAll('.card-tools .closed-tool').forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const card = button.closest('.card');
        card.style.transition = 'opacity 0.4s ease';
        card.style.opacity = card.style.opacity === '0' ? '1' : '0';
        setTimeout(() => {
          card.style.display = card.style.display === 'none' ? '' : 'none';
        }, 400);
      });
    });

    // Search Card
    document.querySelectorAll('.card-tools .search-tool').forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const card = button.closest('.card');
        const search = card.querySelector('.card-search');
        if (search) {
          search.style.transition = 'height 0.1s ease';
          search.style.display = search.style.display === 'none' ? 'block' : 'none';
        }
      });
    });

    // Embed Card
    document.querySelectorAll('.card-tools .embed-tool').forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const card = button.closest('.card');
        const embed = card.querySelector('.card-embed');
        if (embed) {
          embed.style.transition = 'height 0.1s ease';
          embed.style.display = embed.style.display === 'none' ? 'block' : 'none';
        }
      });
    });

    // Expand Card
    document.querySelectorAll('.card-tools .expand-tool').forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const card = button.closest('.card');
        card.classList.toggle('card-fullsize');
      });
    });
  };

  // Widget Tools
  const setupWidgetTools = () => {
    // Close Widget
    document.querySelectorAll('.widget-tools .closed-tool').forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const widget = button.closest('.widget');
        widget.style.transition = 'opacity 0.4s ease';
        widget.style.opacity = widget.style.opacity === '0' ? '1' : '0';
        setTimeout(() => {
          widget.style.display = widget.style.display === 'none' ? '' : 'none';
        }, 400);
      });
    });

    // Expand Widget
    document.querySelectorAll('.widget-tools .expand-tool').forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const widget = button.closest('.widget');
        widget.classList.toggle('widget-fullsize');
      });
    });
  };

  // Alerts
  const setupAlerts = () => {
    // Close Alert
    document.querySelectorAll('.paste-alert .closed').forEach(button => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const alert = button.closest('.paste-alert');
        alert.style.transition = 'opacity 0.35s ease';
        alert.style.opacity = alert.style.opacity === '0' ? '1' : '0';
        setTimeout(() => {
          alert.style.display = alert.style.display === 'none' ? '' : 'none';
        }, 350);
      });
    });

    // Clickable Alert
    document.querySelectorAll('.paste-alert-click').forEach(alert => {
      alert.addEventListener('click', (event) => {
        event.preventDefault();
        alert.style.transition = 'opacity 0.35s ease';
        alert.style.opacity = alert.style.opacity === '0' ? '1' : '0';
        setTimeout(() => {
          alert.style.display = alert.style.display === 'none' ? '' : 'none';
        }, 350);
      });
    });
  };

  // Tooltips and Popovers (Bootstrap 5)
  const setupTooltipsAndPopovers = () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
      new bootstrap.Tooltip(element);
    });
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(element => {
      new bootstrap.Popover(element);
    });
  };

  // Text Manipulation Functions
  const textUtils = {
    showDiv: (id) => {
      const element = document.getElementById(id);
      if (element) element.style.display = 'inline';
    },

    selectText: (id) => {
      const element = document.getElementById(id);
      if (!element) return;
      const selection = window.getSelection();
      const range = document.createRange();
      range.selectNodeContents(element);
      selection.removeAllRanges();
      selection.addRange(range);
    },

    insertTab: (element) => {
      const start = element.selectionStart;
      const end = element.selectionEnd;
      const value = element.value;
      element.value = `${value.substring(0, start)}\t${value.substring(end)}`;
      element.selectionStart = element.selectionEnd = start + 1;
    },

    catchTab: (event) => {
      if (event.key === 'Tab') {
        event.preventDefault();
        textUtils.insertTab(event.target);
      }
    },

    getLines: (text) => text.split('\n'),

    getCaretPosition: (element) => ({
      start: element.selectionStart || 0,
      end: element.selectionEnd || 0
    }),

    setCaretPosition: (element, position) => {
      element.focus();
      element.setSelectionRange(position.start, position.end);
    },

    highlight: (element) => {
      if (!element) return;
      const caret = textUtils.getCaretPosition(element);
      if (!caret.start && !caret.end) return;

      const lines = textUtils.getLines(element.value);
      let newText = '';
      let isHighlighting = false;
      let highlightMode = 0; // 0: unset, 1: removing, 2: adding
      let position = 0;

      for (const line of lines) {
        const lineEnd = position + line.length;
        if (caret.start >= position && caret.start <= lineEnd) isHighlighting = true;

        if (isHighlighting) {
          const isHighlighted = line.startsWith('!highlight!');
          if (!highlightMode) {
            highlightMode = isHighlighted ? 1 : 2;
          }
          if (highlightMode === 1 && isHighlighted) {
            newText += line.substring(11);
          } else if (highlightMode === 2 && !isHighlighted) {
            newText += '!highlight!' + line;
          } else {
            newText += line;
          }
        } else {
          newText += line;
        }
        newText += '\n';
        if (caret.end >= position && caret.end <= lineEnd) isHighlighting = false;
        position = lineEnd + 1;
      }

      element.value = newText.slice(0, -1);
      const newCaret = caret.start + (highlightMode === 1 ? -11 : 11);
      textUtils.setCaretPosition(element, { start: newCaret, end: newCaret });
    },

    toggleListStyle: () => {
      const ol = document.getElementsByTagName('ol')[0];
      if (ol) {
        ol.style.listStyle = ol.style.listStyle.startsWith('none') ? 'decimal' : 'none';
      }
    }
  };

  // Initialize Textarea Tab Support
  const setupTextareaTab = () => {
    document.querySelectorAll('textarea').forEach(textarea => {
      textarea.addEventListener('keydown', textUtils.catchTab);
    });
  };

  // Initialize All Features
  try {
    setupCardTools();
    setupWidgetTools();
    setupAlerts();
    setupTooltipsAndPopovers();
    setupTextareaTab();
  } catch (error) {
    console.error('Error initializing paste.js:', error);
  }

  // Expose text utilities globally (if needed)
window.textUtils = {
    catchTab: function(event) {
        if (event.key === 'Tab') {
            event.preventDefault();
            var textarea = event.target;
            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            textarea.value = textarea.value.substring(0, start) + '\t' + textarea.value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + 1;
        }
        return true;
    },
//    highlight: function(element) {
//        // Implement highlighting (e.g., with Prism.js or GeSHi client-side)
//    }
};
});