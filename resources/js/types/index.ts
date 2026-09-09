export interface User {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'operator';
    pin?: string;
    phone?: string;
}

export interface Event {
    id: number;
    name: string;
    slug: string;
    description?: string;
    event_date?: string;
    location?: string;
    logo_path?: string;
    default_price: number;
    extra_print_price: number;
    watermark_text?: string;
    watermark_logo?: string;
    countdown_seconds: number;
    is_active: boolean;
    custom_settings?: Record<string, any>;
}

export interface TemplateElement {
    id: number;
    template_id: number;
    type: 'photo_slot' | 'text' | 'image' | 'sticker' | 'qr_code' | 'shape';
    slot_index?: number;
    label?: string;
    x: number;
    y: number;
    width: number;
    height: number;
    z_index: number;
    rotation: number;
    border_radius: number;
    border_width: number;
    border_color?: string;
    content?: string;
    font_family?: string;
    font_size?: number;
    font_color?: string;
    font_weight?: string;
    text_align?: string;
    opacity: number;
    is_locked: boolean;
    custom_styles?: Record<string, any>;
}

export interface Template {
    id: number;
    name: string;
    slug: string;
    description?: string;
    category: string;
    photo_count: number;
    width: number;
    height: number;
    orientation: 'portrait' | 'landscape';
    paper_size: string;
    background_color: string;
    background_image?: string;
    overlay_image?: string;
    frame_style: string;
    price: number;
    is_active: boolean;
    is_default: boolean;
    preview_image?: string;
    settings?: Record<string, any>;
    elements?: TemplateElement[];
}

export interface Camera {
    id: number;
    name: string;
    brand: string;
    model?: string;
    adapter: 'canon' | 'sony' | 'nikon' | 'webcam' | 'mock';
    connection_type: string;
    port?: string;
    status: 'ready' | 'busy' | 'error' | 'disconnected';
    battery_level: number;
    storage_remaining?: string;
    iso: string;
    shutter_speed: string;
    aperture: string;
    white_balance: string;
    focus_mode: string;
    is_default: boolean;
    capabilities?: Record<string, boolean>;
    error_message?: string;
}

export interface Printer {
    id: number;
    name: string;
    brand?: string;
    model?: string;
    adapter: 'windows' | 'dyesub' | 'thermal' | 'mock';
    connection_type: string;
    status: 'ready' | 'printing' | 'paper_empty' | 'error' | 'disconnected';
    default_paper_size: string;
    supported_paper_sizes?: string[];
    print_quality: string;
    paper_count: number;
    is_default: boolean;
    error_message?: string;
}

export interface Device {
    id: number;
    device_type: 'camera' | 'printer' | 'display' | 'audio' | 'tablet' | 'sensor';
    name: string;
    identifier?: string;
    status: 'connected' | 'disconnected' | 'warning' | 'error';
    ip_address?: string;
    metadata?: Record<string, any>;
    last_seen?: string;
}

export interface SessionPhoto {
    id: number;
    session_id: string;
    slot_index: number;
    original_path: string;
    edited_path?: string;
    thumbnail_path?: string;
    width: number;
    height: number;
    is_accepted: boolean;
    retake_count: number;
    camera_metadata?: Record<string, any>;
}

export interface FinalPhoto {
    id: number;
    session_id: string;
    file_path: string;
    thumbnail_path?: string;
    width: number;
    height: number;
    mime_type: string;
    file_size?: number;
}

export interface BoothSession {
    id: string;
    session_code: string;
    event_id?: number;
    template_id?: number;
    camera_id?: number;
    printer_id?: number;
    operator_id?: number;
    customer_name?: string;
    customer_phone?: string;
    customer_email?: string;
    status: 'init' | 'template_selected' | 'capturing' | 'reviewing' | 'composing' | 'ready_to_print' | 'printing' | 'completed' | 'cancelled';
    total_photos_required: number;
    photos_captured_count: number;
    current_step: string;
    final_photo_path?: string;
    final_thumbnail_path?: string;
    digital_code?: string;
    qr_code_url?: string;
    payment_status: 'unpaid' | 'pending' | 'paid' | 'free';
    print_status: 'none' | 'queued' | 'printing' | 'printed' | 'failed';
    print_copies: number;
    start_time?: string;
    end_time?: string;
    error_message?: string;
    metadata?: Record<string, any>;
    event?: Event;
    template?: Template;
    camera?: Camera;
    printer?: Printer;
    photos?: SessionPhoto[];
    final_photos?: FinalPhoto[];
}

export interface Promo {
    id: number;
    code: string;
    name: string;
    description?: string;
    discount_type: 'percentage' | 'fixed';
    discount_value: number;
    min_spend: number;
    max_discount?: number;
    usage_limit?: number;
    usage_count: number;
    is_active: boolean;
}

export interface Payment {
    id: number;
    session_id: string;
    method: 'cash' | 'qris' | 'transfer' | 'free' | 'voucher';
    subtotal: number;
    discount_amount: number;
    tax_amount: number;
    total_amount: number;
    amount_paid: number;
    change_amount: number;
    status: 'pending' | 'paid' | 'refunded' | 'failed';
    reference_number?: string;
    paid_at?: string;
}

export interface Setting {
    id: number;
    group: string;
    key: string;
    value: string;
    type: string;
    label?: string;
    description?: string;
}