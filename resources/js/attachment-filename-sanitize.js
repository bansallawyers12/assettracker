/**
 * Mirror DocumentUploadService::sanitizeDisplayFileBase / sanitizeDisplayFileName.
 */
export function sanitizeDisplayFileBase(text) {
    let base = String(text ?? '').trim();
    base = base.replace(/[^a-zA-Z0-9]+/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
    if (!base) {
        base = 'attachment';
    }
    if (base.length > 200) {
        base = base.slice(0, 200).replace(/_+$/, '');
    }
    return base;
}

export function sanitizeDisplayFileName(originalName) {
    const name = String(originalName ?? '');
    const dot = name.lastIndexOf('.');
    const basePart = dot > 0 ? name.slice(0, dot) : name;
    let ext = dot > 0 ? name.slice(dot + 1) : '';
    const base = sanitizeDisplayFileBase(basePart);
    ext = ext.replace(/[^a-zA-Z0-9]+/g, '').toLowerCase();
    return ext ? `${base}.${ext}` : base;
}

export function withSanitizedFileName(file) {
    if (!file) {
        return file;
    }
    const safeName = sanitizeDisplayFileName(file.name);
    if (safeName === file.name) {
        return file;
    }
    return new File([file], safeName, { type: file.type, lastModified: file.lastModified });
}
