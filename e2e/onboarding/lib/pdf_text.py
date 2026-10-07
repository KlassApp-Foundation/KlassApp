#!/usr/bin/env python3
"""Best-effort PDF text extraction using only the Python standard library.

Linear scanner version: inflates FlateDecode streams, then walks the content
byte-by-byte collecting literal strings from text-showing operators. No regex
backtracking, safe for large streams.
"""
import sys
import zlib


def inflate_streams(data: bytes):
    out = []
    idx = 0
    while True:
        i = data.find(b'stream', idx)
        if i == -1:
            break
        j = i + 6
        if data[j:j + 2] == b'\r\n':
            j += 2
        elif data[j:j + 1] in (b'\n', b'\r'):
            j += 1
        k = data.find(b'endstream', j)
        if k == -1:
            break
        chunk = data[j:k].rstrip(b'\r\n')
        idx = k + 9
        try:
            out.append(zlib.decompress(chunk))
        except Exception:
            try:
                out.append(zlib.decompressobj().decompress(chunk))
            except Exception:
                out.append(chunk)
    return out


def strings_from(blob: bytes):
    """Collect (...) literal strings, honoring backslash escapes (linear)."""
    res = []
    n = len(blob)
    i = 0
    while i < n:
        c = blob[i]
        if c == 0x28:  # '('
            buf = bytearray()
            depth = 1
            i += 1
            while i < n and depth > 0:
                ch = blob[i]
                if ch == 0x5C and i + 1 < n:  # backslash
                    buf.append(blob[i + 1])
                    i += 2
                    continue
                if ch == 0x28:  # nested (
                    depth += 1
                elif ch == 0x29:  # ')'
                    depth -= 1
                    if depth == 0:
                        i += 1
                        break
                buf.append(ch)
                i += 1
            res.append(buf.decode('latin-1', 'ignore'))
        else:
            i += 1
    return res


def extract(path: str) -> str:
    data = open(path, 'rb').read()
    parts = strings_from(b'\n'.join(inflate_streams(data)))
    joined = ' '.join(parts)
    return ''.join(ch if 0x20 <= ord(ch) <= 0x7E or ch == '\n' else ' ' for ch in joined)


if __name__ == '__main__':
    print(extract(sys.argv[1])[:20000])
