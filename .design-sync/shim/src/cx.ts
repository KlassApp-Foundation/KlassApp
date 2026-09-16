/**
 * Joins class names, dropping empties and collapsing whitespace.
 *
 * The Blade sources concatenate with `' '` unconditionally, which leaves
 * trailing/double spaces in the rendered `class` attribute. Whitespace is
 * insignificant in a class attribute, so this normalises it — the *set* of
 * classes emitted is identical to the Blade output.
 */
export function cx(...parts: Array<string | false | null | undefined>): string {
    return parts.filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
}
