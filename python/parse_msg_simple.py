#!/usr/bin/env python3
"""
Parse Outlook .msg files and output JSON for Laravel MsgParserService.
Usage: parse_msg_simple.py <path_to_msg_file>

Output: JSON with subject, sender_name, sender_email, recipients, sent_date,
        html_content, text_content, attachments (base64 encoded)
"""

import base64
import json
import sys
from datetime import datetime
from pathlib import Path

import extract_msg

try:
    # Prevent Windows cp1252 console encoding crashes on Unicode characters.
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
except Exception:
    pass


def parse_sender(sender):
    """Extract name and email from sender (string or object)."""
    if sender is None:
        return '', ''
    if isinstance(sender, str):
        # Try to parse "Name <email>" format
        if '<' in sender and '>' in sender:
            parts = sender.split('<', 1)
            name = parts[0].strip().strip('"')
            email = parts[1].rstrip('>').strip()
            return name, email
        return sender, ''
    # Object with name/email attributes
    name = getattr(sender, 'name', None) or getattr(sender, 'senderName', None) or ''
    email = getattr(sender, 'email', None) or getattr(sender, 'senderEmail', None) or ''
    if isinstance(name, bytes):
        name = name.decode('utf-8', errors='replace')
    if isinstance(email, bytes):
        email = email.decode('utf-8', errors='replace')
    return str(name), str(email)


def parse_recipients(to_field):
    """Extract recipients as list of strings."""
    if to_field is None:
        return []
    if isinstance(to_field, (list, tuple)):
        return [str(r) for r in to_field]
    if isinstance(to_field, str):
        # Split by comma or semicolon
        return [r.strip() for r in to_field.replace(';', ',').split(',') if r.strip()]
    return []


def format_date(dt):
    """Format datetime to ISO string."""
    if dt is None:
        return None
    if isinstance(dt, datetime):
        return dt.strftime('%Y-%m-%d %H:%M:%S')
    if isinstance(dt, str):
        return dt
    return str(dt)


def main():
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'Usage: parse_msg_simple.py <path_to_msg_file>'}), file=sys.stderr)
        sys.exit(1)

    msg_path = Path(sys.argv[1]).resolve()
    if not msg_path.exists():
        print(json.dumps({'error': f'File not found: {msg_path}'}), file=sys.stderr)
        sys.exit(1)

    try:
        msg = extract_msg.openMsg(str(msg_path))
    except Exception as e:
        print(json.dumps({'error': str(e)}), file=sys.stderr)
        sys.exit(1)

    try:
        sender_name, sender_email = parse_sender(msg.sender)
        subject = msg.subject or ''
        if isinstance(subject, bytes):
            subject = subject.decode('utf-8', errors='replace')

        body = msg.body or ''
        if isinstance(body, bytes):
            body = body.decode('utf-8', errors='replace')

        html_body = getattr(msg, 'htmlBody', None) or ''
        if isinstance(html_body, bytes):
            html_body = html_body.decode('utf-8', errors='replace')

        to_field = getattr(msg, 'to', None)
        recipients = parse_recipients(to_field)

        sent_date = format_date(msg.date)

        attachments = []
        for att in (msg.attachments or []):
            if getattr(att, 'hidden', False):
                continue
            try:
                filename = att.getFilename() if hasattr(att, 'getFilename') else getattr(att, 'filename', 'attachment')
                if isinstance(filename, bytes):
                    filename = filename.decode('utf-8', errors='replace')
                filename = filename or 'attachment'

                data = None
                if hasattr(att, 'data') and att.data is not None:
                    data = att.data
                elif hasattr(att, 'getStream'):
                    stream = att.getStream()
                    data = stream.read() if stream else None
                if data is None:
                    continue

                content_base64 = base64.b64encode(data).decode('ascii')
                content_type = getattr(att, 'contentType', None) or 'application/octet-stream'
                is_inline = bool(getattr(att, 'cid', None) or getattr(att, 'contentId', None))

                attachments.append({
                    'filename': filename,
                    'content_base64': content_base64,
                    'content_type': content_type,
                    'is_inline': is_inline
                })
            except Exception:
                continue

        result = {
            'subject': subject,
            'sender_name': sender_name,
            'sender_email': sender_email,
            'recipients': recipients,
            'sent_date': sent_date,
            'html_content': html_body,
            'text_content': body,
            'attachments': attachments
        }
        payload = json.dumps(result, ensure_ascii=False)
        try:
            sys.stdout.write(payload)
            sys.stdout.flush()
        except UnicodeEncodeError:
            # Final fallback if stdout encoding cannot handle some characters.
            sys.stdout.buffer.write(payload.encode('utf-8', errors='replace'))
            sys.stdout.flush()
    finally:
        msg.close()


if __name__ == '__main__':
    main()
