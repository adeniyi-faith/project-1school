const norm = (v: string) => v.toLowerCase().replace(/[^a-z0-9]+/g, '');

/** Whether a question's topic belongs to one of the chosen exam topics ("Number bases" matches "Number bases and numerals") */
export function inScope(topic: string | null | undefined, scope: string[]): boolean {
    const t = norm(topic ?? '');
    if (!t) return false;
    return scope.some(s => {
        const n = norm(s);
        return n !== '' && (n === t || n.includes(t) || t.includes(n));
    });
}
