import fs from 'fs';
import path from 'path';

const ARROW = '__BLADE_ARROW__';

function walk(dir, files = []) {
    for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
        const p = path.join(dir, ent.name);
        if (ent.isDirectory()) {
            walk(p, files);
        } else if (ent.name.endsWith('.blade.php') && !p.includes('date-input.blade.php')) {
            files.push(p);
        }
    }

    return files;
}

let changed = 0;

for (const file of walk('resources/views')) {
    let content = fs.readFileSync(file, 'utf8');

    if (!/type=["']date["']/.test(content)) {
        continue;
    }

    const original = content;

    content = content.replace(/->/g, ARROW);

    content = content.replace(
        /<x-text-input([^>]*)\stype="date"([^>]*)\/>/g,
        (match, a, b) => {
            const attrs = (a + b).replace(/\stype="date"/g, '');

            return `<x-date-input${attrs}/>`;
        },
    );

    content = content.replace(
        /<input\s+type="date"([\s\S]*?)>/g,
        (match, attrs) => {
            attrs = attrs.replace(/\s*\/?\s*$/, '').trimEnd();

            return `<x-date-input ${attrs} />`;
        },
    );

    content = content.replace(new RegExp(ARROW, 'g'), '->');

    if (content !== original) {
        fs.writeFileSync(file, content);
        changed++;
        console.log('updated:', file);
    }
}

console.log('files changed:', changed);
