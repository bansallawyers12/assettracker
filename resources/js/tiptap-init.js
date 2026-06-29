import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style';
import { Color } from '@tiptap/extension-color';

const TOOLBAR_BUTTON =
    'inline-flex items-center justify-center rounded px-1.5 py-1 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 disabled:opacity-40 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white';

const TOOLBAR_BUTTON_ACTIVE =
    'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200';

function escapeSelectorId(id) {
    return (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(id) : id.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
}

function resolveElement(target) {
    if (!target) {
        return null;
    }

    return typeof target === 'string'
        ? document.querySelector(target.startsWith('#') ? target : `#${target}`)
        : target;
}

function fieldValue(el) {
    if (!el) {
        return '';
    }

    if (el._richTextEditor) {
        syncTextarea(el, el._richTextEditor);
    }

    return el.value ?? '';
}

function cleanupEditorShell(el) {
    if (el.id) {
        const editorId = `${el.id}-editor`;
        const label = document.querySelector(`label[for="${escapeSelectorId(editorId)}"]`);

        if (label) {
            label.setAttribute('for', el.id);
        }
    }

    el._richTextWrapper?.remove();
    delete el._richTextWrapper;

    el.classList.remove('sr-only');
    el.removeAttribute('aria-hidden');
    el.tabIndex = 0;
}

function createToolbarButton(toolbar, { label, title, onClick, isActive, isDisabled }) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = TOOLBAR_BUTTON;
    button.title = title;
    button.setAttribute('aria-label', title);
    button.innerHTML = label;

    button.addEventListener('mousedown', (event) => {
        event.preventDefault();
    });

    button.addEventListener('click', (event) => {
        event.preventDefault();
        onClick();
    });

    const refresh = () => {
        button.classList.toggle(TOOLBAR_BUTTON_ACTIVE, Boolean(isActive?.()));
        button.disabled = Boolean(isDisabled?.());
    };

    toolbar._refreshers.push(refresh);
    toolbar.appendChild(button);
    refresh();

    return button;
}

function createDivider(toolbar) {
    const divider = document.createElement('span');
    divider.className = 'mx-1 h-5 w-px shrink-0 bg-gray-200 dark:bg-gray-600';
    divider.setAttribute('aria-hidden', 'true');
    toolbar.appendChild(divider);
}

function buildToolbar(editor, toolbar) {
    toolbar._refreshers = [];
    toolbar.className =
        'rich-text-toolbar flex flex-wrap items-center gap-0.5 rounded-t-md border border-b-0 border-gray-300 bg-gray-50 px-2 py-1.5 dark:border-gray-600 dark:bg-gray-800';

    createToolbarButton(toolbar, {
        label: '↶',
        title: 'Undo',
        onClick: () => editor.chain().focus().undo().run(),
        isDisabled: () => !editor.can().chain().focus().undo().run(),
    });

    createToolbarButton(toolbar, {
        label: '↷',
        title: 'Redo',
        onClick: () => editor.chain().focus().redo().run(),
        isDisabled: () => !editor.can().chain().focus().redo().run(),
    });

    createDivider(toolbar);

    const headingSelect = document.createElement('select');
    headingSelect.className =
        'rounded border-0 bg-transparent py-1 pl-1 pr-6 text-sm text-gray-700 focus:ring-0 dark:text-gray-200';
    headingSelect.innerHTML = `
        <option value="paragraph">Paragraph</option>
        <option value="1">Heading 1</option>
        <option value="2">Heading 2</option>
        <option value="3">Heading 3</option>
    `;

    const syncHeadingSelect = () => {
        if (editor.isActive('heading', { level: 1 })) {
            headingSelect.value = '1';
        } else if (editor.isActive('heading', { level: 2 })) {
            headingSelect.value = '2';
        } else if (editor.isActive('heading', { level: 3 })) {
            headingSelect.value = '3';
        } else {
            headingSelect.value = 'paragraph';
        }
    };

    headingSelect.addEventListener('change', () => {
        const level = headingSelect.value;

        if (level === 'paragraph') {
            editor.chain().focus().setParagraph().run();
        } else {
            editor.chain().focus().setHeading({ level: Number(level) }).run();
        }
    });

    toolbar._refreshers.push(syncHeadingSelect);
    toolbar.appendChild(headingSelect);

    createDivider(toolbar);

    createToolbarButton(toolbar, {
        label: '<strong>B</strong>',
        title: 'Bold',
        onClick: () => editor.chain().focus().toggleBold().run(),
        isActive: () => editor.isActive('bold'),
    });

    createToolbarButton(toolbar, {
        label: '<em>I</em>',
        title: 'Italic',
        onClick: () => editor.chain().focus().toggleItalic().run(),
        isActive: () => editor.isActive('italic'),
    });

    createToolbarButton(toolbar, {
        label: '<u>U</u>',
        title: 'Underline',
        onClick: () => editor.chain().focus().toggleUnderline().run(),
        isActive: () => editor.isActive('underline'),
    });

    createToolbarButton(toolbar, {
        label: '✕',
        title: 'Clear formatting',
        onClick: () => editor.chain().focus().clearNodes().unsetAllMarks().run(),
    });

    createDivider(toolbar);

    const colorInput = document.createElement('input');
    colorInput.type = 'color';
    colorInput.value = '#111827';
    colorInput.title = 'Text color';
    colorInput.className =
        'h-7 w-7 cursor-pointer rounded border border-gray-300 bg-white p-0.5 dark:border-gray-600 dark:bg-gray-700';
    colorInput.addEventListener('mousedown', (event) => {
        event.preventDefault();
    });
    colorInput.addEventListener('input', () => {
        editor.chain().focus().setColor(colorInput.value).run();
    });
    toolbar.appendChild(colorInput);

    createDivider(toolbar);

    createToolbarButton(toolbar, {
        label: '⫷',
        title: 'Align left',
        onClick: () => editor.chain().focus().setTextAlign('left').run(),
        isActive: () => editor.isActive({ textAlign: 'left' }),
    });

    createToolbarButton(toolbar, {
        label: '☰',
        title: 'Align center',
        onClick: () => editor.chain().focus().setTextAlign('center').run(),
        isActive: () => editor.isActive({ textAlign: 'center' }),
    });

    createToolbarButton(toolbar, {
        label: '⫸',
        title: 'Align right',
        onClick: () => editor.chain().focus().setTextAlign('right').run(),
        isActive: () => editor.isActive({ textAlign: 'right' }),
    });

    createDivider(toolbar);

    createToolbarButton(toolbar, {
        label: '•',
        title: 'Bullet list',
        onClick: () => editor.chain().focus().toggleBulletList().run(),
        isActive: () => editor.isActive('bulletList'),
    });

    createToolbarButton(toolbar, {
        label: '1.',
        title: 'Numbered list',
        onClick: () => editor.chain().focus().toggleOrderedList().run(),
        isActive: () => editor.isActive('orderedList'),
    });

    createDivider(toolbar);

    createToolbarButton(toolbar, {
        label: '🔗',
        title: 'Link',
        onClick: () => {
            const previousUrl = editor.getAttributes('link').href ?? '';
            const url = window.prompt('Enter URL', previousUrl);

            if (url === null) {
                return;
            }

            if (url === '') {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();
                return;
            }

            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        },
        isActive: () => editor.isActive('link'),
    });

    const refreshToolbar = () => {
        toolbar._refreshers.forEach((refresh) => refresh());
    };

    editor.on('selectionUpdate', refreshToolbar);
    editor.on('transaction', refreshToolbar);

    return refreshToolbar;
}

function createEditorShell(el) {
    const wrapper = document.createElement('div');
    wrapper.className = 'rich-text-editor-wrapper mt-1 w-full';
    wrapper.dataset.richTextWrapperFor = el.id;

    const toolbar = document.createElement('div');
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Text formatting');

    const editorEl = document.createElement('div');
    editorEl.className =
        'rich-text-editor rounded-b-md border border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700';

    wrapper.appendChild(toolbar);
    wrapper.appendChild(editorEl);

    el.insertAdjacentElement('afterend', wrapper);
    el.classList.add('sr-only');
    el.setAttribute('aria-hidden', 'true');
    el.tabIndex = -1;

    return { wrapper, toolbar, editorEl };
}

function wireEditorLabel(el, editableEl) {
    if (!el.id || !editableEl) {
        return;
    }

    if (!editableEl.id) {
        editableEl.id = `${el.id}-editor`;
    }

    const label = document.querySelector(`label[for="${escapeSelectorId(el.id)}"]`);

    if (label) {
        label.setAttribute('for', editableEl.id);
    }
}

function syncTextarea(el, editor) {
    el.value = editor.getHTML();
    el.dispatchEvent(new Event('input', { bubbles: true }));
}

/**
 * Initialize Tiptap on a textarea or [data-rich-text] element.
 */
export function initRichTextEditor(target, options = {}) {
    const el = resolveElement(target);

    if (!el) {
        return Promise.resolve(null);
    }

    if (el._richTextInitPromise) {
        return el._richTextInitPromise;
    }

    if (el.dataset.richTextInit === 'true') {
        return Promise.resolve(el._richTextEditor ?? null);
    }

    if (!el.id) {
        el.id = `rich-text-${crypto.randomUUID?.() ?? Date.now()}`;
    }

    const height = el.dataset.richTextHeight ? parseInt(el.dataset.richTextHeight, 10) : 200;
    const placeholder = el.dataset.richTextPlaceholder || 'Write your message here...';
    const initialContent = el.value ?? '';

    el._richTextInitPromise = Promise.resolve().then(() => {
        const { wrapper, toolbar, editorEl } = createEditorShell(el);
        editorEl.style.minHeight = `${height}px`;

        let editor;

        try {
            editor = new Editor({
                element: editorEl,
                extensions: [
                    StarterKit,
                    Underline,
                    TextStyle,
                    Color,
                    Link.configure({
                        openOnClick: false,
                        autolink: true,
                        linkOnPaste: true,
                        defaultProtocol: 'https',
                        HTMLAttributes: {
                            rel: 'noopener noreferrer',
                        },
                    }),
                    TextAlign.configure({
                        types: ['heading', 'paragraph'],
                    }),
                    Placeholder.configure({
                        placeholder,
                    }),
                ],
                content: initialContent,
                ...options,
                onUpdate: ({ editor: currentEditor }) => {
                    syncTextarea(el, currentEditor);
                    options.onUpdate?.({ editor: currentEditor });
                },
            });
        } catch (error) {
            cleanupEditorShell(el);
            throw error;
        }

        buildToolbar(editor, toolbar);
        wireEditorLabel(el, editor.view.dom);
        syncTextarea(el, editor);

        el._richTextEditor = editor;
        el._richTextWrapper = wrapper;
        el.dataset.richTextInit = 'true';

        return editor;
    }).catch((error) => {
        delete el._richTextInitPromise;
        delete el.dataset.richTextInit;
        throw error;
    });

    return el._richTextInitPromise;
}

export function initRichTextEditors(root = document, options = {}) {
    const includeDeferred = options.includeDeferred ?? false;
    const promises = [];

    root.querySelectorAll('[data-rich-text]:not([data-rich-text-init="true"])').forEach((el) => {
        if (!includeDeferred && el.dataset.richTextDefer === 'true') {
            return;
        }

        promises.push(initRichTextEditor(el));
    });

    return Promise.all(promises);
}

export function getRichTextContent(target) {
    return fieldValue(resolveElement(target));
}

export function isRichTextEmpty(target) {
    const el = resolveElement(target);

    if (!el) {
        return true;
    }

    if (el._richTextEditor) {
        return el._richTextEditor.isEmpty;
    }

    const div = document.createElement('div');
    div.innerHTML = el.value ?? '';

    return (div.textContent ?? '').trim() === '';
}

export function setRichTextContent(target, content) {
    const el = resolveElement(target);

    if (!el) {
        return;
    }

    if (el._richTextEditor) {
        el._richTextEditor.commands.setContent(content ?? '', { emitUpdate: false });
        syncTextarea(el, el._richTextEditor);
        return;
    }

    el.value = content ?? '';
}

export function destroyRichTextEditor(target) {
    const el = resolveElement(target);

    if (!el) {
        return;
    }

    el._richTextEditor?.destroy();
    delete el._richTextEditor;

    cleanupEditorShell(el);

    delete el.dataset.richTextInit;
    delete el._richTextInitPromise;
}
