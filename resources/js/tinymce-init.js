import tinymce from 'tinymce';

import 'tinymce/icons/default/icons.min.js';
import 'tinymce/themes/silver/theme.min.js';
import 'tinymce/models/dom/model.min.js';
import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide/content.js';
import 'tinymce/skins/content/default/content.js';

import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/media';
import 'tinymce/plugins/table';
import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/help';
import 'tinymce/plugins/help/js/i18n/keynav/en';

const DEFAULT_OPTIONS = {
    license_key: 'gpl',
    promotion: false,
    branding: false,
    height: 200,
    menubar: false,
    placeholder: 'Write your message here...',
    plugins: 'lists advlist autolink link image media table code fullscreen help',
    toolbar: 'undo redo | blocks | bold italic underline removeformat | forecolor | alignleft aligncenter alignright | bullist numlist | table | link image media | code fullscreen help',
    relative_urls: false,
    remove_script_host: false,
    skin_url: 'default',
    content_css: 'default',
};

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

    const editor = el.id ? tinymce.get(el.id) : null;

    return editor ? editor.getContent() : (el.value ?? '');
}

/**
 * Initialize TinyMCE on a textarea or [data-rich-text] element.
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
        return Promise.resolve(tinymce.get(el.id) ?? null);
    }

    if (!el.id) {
        el.id = `rich-text-${crypto.randomUUID?.() ?? Date.now()}`;
    }

    const height = el.dataset.richTextHeight ? parseInt(el.dataset.richTextHeight, 10) : undefined;
    const placeholder = el.dataset.richTextPlaceholder;

    el._richTextInitPromise = tinymce.init({
        ...DEFAULT_OPTIONS,
        ...(height ? { height } : {}),
        ...(placeholder ? { placeholder } : {}),
        ...options,
        target: el,
    }).then((editors) => {
        el.dataset.richTextInit = 'true';
        const editor = editors?.[0] ?? tinymce.get(el.id) ?? null;
        editor?.fire('ResizeEditor');

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

export function setRichTextContent(target, content) {
    const el = resolveElement(target);

    if (!el) {
        return;
    }

    const editor = el.id ? tinymce.get(el.id) : null;

    if (editor) {
        editor.setContent(content ?? '');
        return;
    }

    el.value = content ?? '';
}

export function destroyRichTextEditor(target) {
    const el = resolveElement(target);

    if (!el?.id) {
        return;
    }

    tinymce.get(el.id)?.remove();
    delete el.dataset.richTextInit;
    delete el._richTextInitPromise;
}

export { tinymce };
