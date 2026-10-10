import { isValidElement, type ReactElement, type ReactNode } from 'react';

/**
 * The shadcn components in this project sit on Base UI, which swaps the element a component
 * renders through a `render` prop instead of shadcn's `asChild`. Many pages were written with
 * `asChild` (a Button that wraps a Link, a menu item that wraps a Link), so this turns that
 * pattern into the `render` form. Without it the page ends up with a <button> inside an <a>.
 */
export function asChildProps(asChild: boolean | undefined, children: ReactNode) {
    if (asChild && isValidElement(children)) {
        const el = children as ReactElement<{ children?: ReactNode }>;
        return { render: el, nativeButton: false as const, children: el.props.children };
    }
    return { children };
}
