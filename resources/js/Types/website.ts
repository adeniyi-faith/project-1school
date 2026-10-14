import type { FormDataConvertible } from '@inertiajs/core';

/** Shapes shared by the website screens (overview, themes, pages, news, pictures, settings) */

export interface WebsiteShell { publicUrl: string; enabled: boolean; canEdit: boolean }

export interface Field {
    key: string; label: string; kind: 'text' | 'textarea' | 'select' | 'number' | 'image' | 'list';
    options?: Record<string, string>; min?: number; max?: number; limit?: number; noun?: string; hint?: string; format?: boolean; fields?: Field[];
}
export interface BlockType { type: string; label: string; help: string; fields: Field[]; default: Record<string, unknown> }
/** One section of a page; the other keys are its fields (heading, body, items ...) */
export type Block = { type: string; hidden?: boolean; [key: string]: FormDataConvertible };

export interface MediaItem { id: number; path: string; name: string; alt: string | null; width: number | null; height: number | null; size: number | null; url?: string; used?: boolean }

export interface ThemeOption { key: string; label: string; note: string; brand: string; brand2: string; bg: string; soft: string; serif: boolean }
export interface Palette { name: string; color: string; color2: string }

export interface SitePage {
    id: number; slug: string; title: string; seo_title: string | null; seo_description: string | null; blocks: Block[] | null;
    is_published: boolean; is_home: boolean; show_in_menu: boolean;
}
export interface PageRow { id: number; slug: string; title: string; is_published: boolean; is_home: boolean; show_in_menu: boolean; sections: number; updated_at: string; url: string }
export interface PageTemplate { key: string; label: string; help: string }

export type PostStatus = 'draft' | 'published' | 'scheduled';
export interface PostRow {
    id: number; title: string; category: string; categoryLabel: string; status: PostStatus; published_at: string | null; event_date: string | null;
    is_featured: boolean; cover: string | null; author: string | null; updated_at: string; url: string;
}
export interface PostForm {
    id: number | null; title: string; slug: string; category: string; excerpt: string; body: string; cover_image: string; status: 'draft' | 'published';
    published_at: string; event_date: string; event_place: string; is_featured: boolean; seo_title: string; seo_description: string; url: string | null;
}

export interface AlbumRow { id: number; title: string; description: string | null; event_date: string | null; is_published: boolean; photos: number; cover: string | null; url: string }
export interface AlbumForm { id: number; title: string; description: string; event_date: string; is_published: boolean; cover_media_id: number | null; url: string }
export interface AlbumPhoto { id: number; media_id: number; path: string; caption: string; alt: string | null }
