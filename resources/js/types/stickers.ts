export interface PhotoboothSticker {
    id: string;
    name: string;
    category: 'none' | 'icon' | 'text' | 'pattern' | 'character';
    symbol?: string;
    bg?: string;
    color?: string;
    badgeText?: string;
    badgeSubtext?: string;
    isTextStamp?: boolean;
    hasStripes?: boolean;
}

export const PHOTOBOOTH_STICKERS: PhotoboothSticker[] = [
    { id: 'none', name: 'Tanpa Stiker / Polos', category: 'none', symbol: '🚫' },
    { 
        id: 'cherries', 
        name: 'Cherries with Bow', 
        category: 'icon', 
        symbol: '🍒', 
        bg: '#fdf2f8' 
    },
    { 
        id: 'pink_bows', 
        name: 'Pink Bows & Stripes', 
        category: 'pattern', 
        symbol: '🎀', 
        bg: '#fce7f3',
        color: '#f43f5e',
        hasStripes: true,
    },
    { 
        id: 'friendship_nutrition', 
        name: 'Friendship Nutrition', 
        category: 'text', 
        isTextStamp: true, 
        badgeText: 'FRIENDSHIP NUTRITION', 
        badgeSubtext: '100% daily value',
        bg: '#0f172a',
        color: '#ffffff'
    },
    { id: 'pink_diamond', name: 'Pink Diamond', category: 'icon', symbol: '💎', bg: '#fce7f3' },
    { id: 'blue_flower', name: 'Crystal Flower', category: 'icon', symbol: '🌸', bg: '#e0f2fe' },
    { id: 'blue_butterfly', name: 'Blue Butterfly', category: 'icon', symbol: '🦋', bg: '#e0f2fe' },
    { id: 'pink_leopard', name: 'Pink Leopard', category: 'pattern', symbol: '🐆', bg: '#fbcfe8' },
    { id: 'silver_leopard', name: 'Silver Leopard', category: 'pattern', symbol: '🐾', bg: '#f1f5f9' },
    { id: 'pink_glitter', name: 'Pink Glitter', category: 'pattern', symbol: '✨', bg: '#fdf2f8' },
    { id: 'tv_bear', name: 'Cute TV Bear', category: 'character', symbol: '🐻', bg: '#fef3c7' },
    { id: 'tokyo_tower', name: 'Tokyo Tower', category: 'icon', symbol: '🗼', bg: '#fee2e2' },
    { id: 'pink_hearts', name: 'Pink Hearts', category: 'icon', symbol: '💕', bg: '#ffe4e6' },
    { id: 'cherries', name: 'Sweet Cherries', category: 'icon', symbol: '🍒', bg: '#ffe4e6' },
    { 
        id: 'caught_moment', 
        name: 'Caught in the Moment', 
        category: 'text', 
        isTextStamp: true, 
        badgeText: 'CAUGHT IN THE MOMENT', 
        badgeSubtext: 'love captured forever',
        bg: '#0f172a',
        color: '#ffffff'
    },
    { 
        id: 'save_date', 
        name: 'SAVE THE DATE', 
        category: 'text', 
        isTextStamp: true, 
        badgeText: 'SAVE THE DATE', 
        badgeSubtext: 'the wedding celebration',
        bg: '#f8fafc',
        color: '#0f172a'
    },
    { 
        id: 'save_date_script', 
        name: 'Save The Date', 
        category: 'text', 
        isTextStamp: true, 
        badgeText: 'Save the Date', 
        badgeSubtext: 'special memory',
        bg: '#f1f5f9',
        color: '#334155'
    },
    { id: 'blue_polka', name: 'Blue Polka Dots', category: 'pattern', symbol: '🔵', bg: '#dbeafe' },
    { id: 'black_polka', name: 'Black Polka Dots', category: 'pattern', symbol: '⚫', bg: '#e2e8f0' },
    { 
        id: 'happy_birthday', 
        name: 'HAPPY BIRTHDAY', 
        category: 'text', 
        isTextStamp: true, 
        badgeText: 'HAPPY BIRTHDAY', 
        badgeSubtext: 'make a wish',
        bg: '#f3e8ff',
        color: '#6b21a8'
    },
    { id: 'birthday_cake', name: 'Birthday Cake', category: 'icon', symbol: '🎂', bg: '#dcfce7' },
    { 
        id: 'endless_love', 
        name: 'Endless Love', 
        category: 'text', 
        isTextStamp: true, 
        badgeText: 'Endless Love', 
        badgeSubtext: 'always & forever',
        bg: '#fce7f3',
        color: '#9d174d'
    },
    { id: 'glitter_star', name: 'Pink Glitter Star', category: 'icon', symbol: '⭐', bg: '#fdf2f8' },
    { id: 'denim_heart', name: 'Denim Heart', category: 'icon', symbol: '🤎', bg: '#e0e7ff' },
    { id: 'magic_star', name: 'Stars & Swirl', category: 'icon', symbol: '🌟', bg: '#fdf4ff' },
    { id: 'clover', name: 'Lucky Clover', category: 'icon', symbol: '🍀', bg: '#dcfce7' },
    { id: 'sparkles', name: 'Sparkles Duo', category: 'icon', symbol: '✨', bg: '#f8fafc' },
    { id: 'brown_bow', name: 'Brown Ribbon', category: 'icon', symbol: '🎀', bg: '#fef3c7' },
    { id: 'pink_bow', name: 'Pink Coquette Bow', category: 'icon', symbol: '🎀', bg: '#ffe4e6' },
    { id: 'black_bow', name: 'Black Satin Bow', category: 'icon', symbol: '🎀', bg: '#e2e8f0' },
    { id: 'grid_dots', name: 'Grid Dots', category: 'pattern', symbol: '🏁', bg: '#f8fafc' },
    { id: 'balloons', name: 'Red Balloons', category: 'icon', symbol: '🎈', bg: '#fee2e2' },
    { id: 'pixel_heart', name: '8-Bit Pixel Heart', category: 'icon', symbol: '👾', bg: '#fee2e2' },
    { id: 'love_candy', name: 'Love Candy', category: 'icon', symbol: '🍬', bg: '#fce7f3' },
    { id: 'kiss_lips', name: 'Kiss Lips', category: 'icon', symbol: '💋', bg: '#ffe4e6' },
    { 
        id: 'game_over', 
        name: 'GAME START', 
        category: 'text', 
        isTextStamp: true, 
        badgeText: 'GAME START', 
        badgeSubtext: 'stage 1 clear',
        bg: '#052e16',
        color: '#22c55e'
    },
    { id: 'cat_doodle', name: 'Cute Cat Doodle', category: 'character', symbol: '🐱', bg: '#fef3c7' },
    { id: 'anime_eyes', name: 'Big Anime Eyes', category: 'character', symbol: '👀', bg: '#f1f5f9' },
    { id: 'y2k_stars', name: 'Y2K Sparkle Stars', category: 'icon', symbol: '💫', bg: '#f5f3ff' },
];
