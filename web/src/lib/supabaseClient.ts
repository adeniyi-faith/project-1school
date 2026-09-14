import { createClient } from "@supabase/supabase-js";

// Supabase is used here for login, file storage, and realtime updates.
// Laravel (see lib/api.ts) is still where all the school/user business
// rules live — this client only talks to Supabase for the things
// Supabase itself is responsible for.
const supabaseUrl = import.meta.env.VITE_SUPABASE_URL as string;
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY as string;

export const supabase = createClient(supabaseUrl, supabaseAnonKey);
