import { useState } from 'react';

/** Shows the first two lines of a notice; tap it to read all of it, tap again to fold it back. */
export default function ExpandableText({ text, className = '' }: { text: string; className?: string }) {
    const [open, setOpen] = useState(false);
    const long = text.length > 90 || text.includes('\n');

    if (!long) return <p className={className}>{text}</p>;

    return (
        <button type="button" onClick={() => setOpen((o) => !o)} aria-expanded={open} className="block w-full text-left">
            <span className={`block whitespace-pre-line ${className} ${open ? '' : 'line-clamp-2'}`}>{text}</span>
            <span className="mt-0.5 block text-xs font-medium text-indigo-600 dark:text-indigo-400">{open ? 'Show less' : 'Read more'}</span>
        </button>
    );
}
