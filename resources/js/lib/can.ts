import { usePage } from '@inertiajs/react';
import type { PageProps } from '@/Types';

/**
 * `const can = useCan(); can('students.create')`: true when the signed-in person holds the permission.
 * Use it to hide buttons a person could not use anyway; the server still refuses them.
 */
export function useCan(): (permission: string) => boolean {
    const permissions = usePage<PageProps>().props.auth?.permissions ?? [];

    return (permission: string) => permissions.includes('*') || permissions.includes(permission);
}
