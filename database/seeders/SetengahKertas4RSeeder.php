<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Template;
use App\Models\TemplateElement;

class SetengahKertas4RSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // =========================================================================
        // A. FORMAT STRIP (SETENGAH KERTAS 4R / 2x6" / 600x1800 px)
        // =========================================================================

        // --- 3 FOTO STRIP PRESETS ---
        // 1. Strip 3 Foto - BeautyPlus Pink Bows
        $t3Pink = Template::updateOrCreate(
            ['slug' => 'strip-setengah-4r-3-foto-pink'],
            [
                'name' => 'Strip 3 Foto (BeautyPlus Pink Bows)',
                'description' => 'Tema garis-garis pink pastel manis dengan aksen pita ribbon dan hati ganda.',
                'category' => 'Strip',
                'paper_size' => 'Strip 2x6',
                'width' => 600,
                'height' => 1800,
                'orientation' => 'portrait',
                'photo_count' => 3,
                'background_color' => '#ffffff',
                'frame_style' => 'strip',
                'is_active' => true,
                'is_default' => true,
            ]
        );
        $t3Pink->elements()->delete();
        for ($i = 1; $i <= 3; $i++) {
            TemplateElement::create([
                'template_id' => $t3Pink->id,
                'type' => 'photo_slot',
                'slot_index' => $i,
                'label' => "Foto {$i}",
                'x' => 8,
                'y' => 4.5 + ($i - 1) * 28.5,
                'width' => 84,
                'height' => 26.5,
                'border_radius' => 8,
                'border_width' => 0,
                'z_index' => 1,
            ]);
        }
        TemplateElement::create([
            'template_id' => $t3Pink->id,
            'type' => 'text',
            'content' => '{date} • BEAUTYPLUS',
            'x' => 8,
            'y' => 92,
            'width' => 60,
            'height' => 4,
            'font_size' => 18,
            'font_color' => '#db2777',
            'text_align' => 'left',
            'z_index' => 2,
        ]);
        TemplateElement::create([
            'template_id' => $t3Pink->id,
            'type' => 'qr_code',
            'label' => 'QR',
            'x' => 74,
            'y' => 89.5,
            'width' => 18,
            'height' => 8,
            'z_index' => 3,
        ]);

        // 2. Strip 3 Foto - Minimalist White Studio
        $t3White = Template::updateOrCreate(
            ['slug' => 'strip-setengah-4r-3-foto-white'],
            [
                'name' => 'Strip 3 Foto (Minimalist White Studio)',
                'description' => 'Desain putih bersih minimalis modern ala studio foto profesional.',
                'category' => 'Strip',
                'paper_size' => 'Strip 2x6',
                'width' => 600,
                'height' => 1800,
                'orientation' => 'portrait',
                'photo_count' => 3,
                'background_color' => '#ffffff',
                'frame_style' => 'strip',
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $t3White->elements()->delete();
        for ($i = 1; $i <= 3; $i++) {
            TemplateElement::create([
                'template_id' => $t3White->id,
                'type' => 'photo_slot',
                'slot_index' => $i,
                'label' => "Foto {$i}",
                'x' => 8,
                'y' => 4.5 + ($i - 1) * 28.5,
                'width' => 84,
                'height' => 26.5,
                'border_radius' => 8,
                'border_width' => 1,
                'border_color' => '#e2e8f0',
                'z_index' => 1,
            ]);
        }
        TemplateElement::create([
            'template_id' => $t3White->id,
            'type' => 'text',
            'content' => '{date} • STUDIO WHITE',
            'x' => 8,
            'y' => 92,
            'width' => 60,
            'height' => 4,
            'font_size' => 18,
            'font_color' => '#64748b',
            'text_align' => 'left',
            'z_index' => 2,
        ]);
        TemplateElement::create([
            'template_id' => $t3White->id,
            'type' => 'qr_code',
            'label' => 'QR',
            'x' => 74,
            'y' => 89.5,
            'width' => 18,
            'height' => 8,
            'z_index' => 3,
        ]);

        // 3. Strip 3 Foto - Dark Velvet Studio
        $t3Dark = Template::updateOrCreate(
            ['slug' => 'strip-setengah-4r-3-foto-dark'],
            [
                'name' => 'Strip 3 Foto (Dark Velvet Studio)',
                'description' => 'Latar belakang hitam pekat mewah dengan aksen border putih elegan.',
                'category' => 'Strip',
                'paper_size' => 'Strip 2x6',
                'width' => 600,
                'height' => 1800,
                'orientation' => 'portrait',
                'photo_count' => 3,
                'background_color' => '#0f172a',
                'frame_style' => 'strip',
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $t3Dark->elements()->delete();
        for ($i = 1; $i <= 3; $i++) {
            TemplateElement::create([
                'template_id' => $t3Dark->id,
                'type' => 'photo_slot',
                'slot_index' => $i,
                'label' => "Foto {$i}",
                'x' => 8,
                'y' => 4.5 + ($i - 1) * 28.5,
                'width' => 84,
                'height' => 26.5,
                'border_radius' => 8,
                'border_width' => 2,
                'border_color' => '#334155',
                'z_index' => 1,
            ]);
        }
        TemplateElement::create([
            'template_id' => $t3Dark->id,
            'type' => 'text',
            'content' => '{date} • DARK VELVET',
            'x' => 8,
            'y' => 92,
            'width' => 60,
            'height' => 4,
            'font_size' => 18,
            'font_color' => '#f8fafc',
            'text_align' => 'left',
            'z_index' => 2,
        ]);
        TemplateElement::create([
            'template_id' => $t3Dark->id,
            'type' => 'qr_code',
            'label' => 'QR',
            'x' => 74,
            'y' => 89.5,
            'width' => 18,
            'height' => 8,
            'z_index' => 3,
        ]);

        // 4. Strip 3 Foto - Sweet Lavender
        $t3Lavender = Template::updateOrCreate(
            ['slug' => 'strip-setengah-4r-3-foto-lavender'],
            [
                'name' => 'Strip 3 Foto (Sweet Lavender Lilac)',
                'description' => 'Tema pastel ungu lilac manis dan estetik.',
                'category' => 'Strip',
                'paper_size' => 'Strip 2x6',
                'width' => 600,
                'height' => 1800,
                'orientation' => 'portrait',
                'photo_count' => 3,
                'background_color' => '#f3e8ff',
                'frame_style' => 'strip',
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $t3Lavender->elements()->delete();
        for ($i = 1; $i <= 3; $i++) {
            TemplateElement::create([
                'template_id' => $t3Lavender->id,
                'type' => 'photo_slot',
                'slot_index' => $i,
                'label' => "Foto {$i}",
                'x' => 8,
                'y' => 4.5 + ($i - 1) * 28.5,
                'width' => 84,
                'height' => 26.5,
                'border_radius' => 8,
                'border_width' => 0,
                'z_index' => 1,
            ]);
        }
        TemplateElement::create([
            'template_id' => $t3Lavender->id,
            'type' => 'text',
            'content' => '{date} • SWEET LAVENDER',
            'x' => 8,
            'y' => 92,
            'width' => 60,
            'height' => 4,
            'font_size' => 18,
            'font_color' => '#7e22ce',
            'text_align' => 'left',
            'z_index' => 2,
        ]);
        TemplateElement::create([
            'template_id' => $t3Lavender->id,
            'type' => 'qr_code',
            'label' => 'QR',
            'x' => 74,
            'y' => 89.5,
            'width' => 18,
            'height' => 8,
            'z_index' => 3,
        ]);

        // --- 4 FOTO STRIP PRESETS ---
        // 5. Strip 4 Foto - Life4Cuts Classic Korea
        $t4Korea = Template::updateOrCreate(
            ['slug' => 'strip-setengah-4r-4-foto'],
            [
                'name' => 'Strip 4 Foto (Life4Cuts Korea White)',
                'description' => 'Format 4 slot vertikal paling populer ala Insaengnekeot Korea.',
                'category' => 'Strip',
                'paper_size' => 'Strip 2x6',
                'width' => 600,
                'height' => 1800,
                'orientation' => 'portrait',
                'photo_count' => 4,
                'background_color' => '#ffffff',
                'frame_style' => 'strip',
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $t4Korea->elements()->delete();
        for ($i = 1; $i <= 4; $i++) {
            TemplateElement::create([
                'template_id' => $t4Korea->id,
                'type' => 'photo_slot',
                'slot_index' => $i,
                'label' => "Foto {$i}",
                'x' => 8,
                'y' => 4.5 + ($i - 1) * 21.0,
                'width' => 84,
                'height' => 19.5,
                'border_radius' => 8,
                'border_width' => 0,
                'z_index' => 1,
            ]);
        }
        TemplateElement::create([
            'template_id' => $t4Korea->id,
            'type' => 'text',
            'content' => '{date} • INSAENGNEKEOT',
            'x' => 8,
            'y' => 92,
            'width' => 60,
            'height' => 4,
            'font_size' => 18,
            'font_color' => '#94a3b8',
            'text_align' => 'left',
            'z_index' => 2,
        ]);
        TemplateElement::create([
            'template_id' => $t4Korea->id,
            'type' => 'qr_code',
            'label' => 'QR',
            'x' => 74,
            'y' => 89.5,
            'width' => 18,
            'height' => 8,
            'z_index' => 3,
        ]);

        // 6. Strip 4 Foto - Y2K Berry Pink
        $t4Pink = Template::updateOrCreate(
            ['slug' => 'strip-setengah-4r-4-foto-pink'],
            [
                'name' => 'Strip 4 Foto (Y2K Berry Pink)',
                'description' => 'Background pink pastel ceria bernuansa Y2K pop.',
                'category' => 'Strip',
                'paper_size' => 'Strip 2x6',
                'width' => 600,
                'height' => 1800,
                'orientation' => 'portrait',
                'photo_count' => 4,
                'background_color' => '#fce7f3',
                'frame_style' => 'strip',
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $t4Pink->elements()->delete();
        for ($i = 1; $i <= 4; $i++) {
            TemplateElement::create([
                'template_id' => $t4Pink->id,
                'type' => 'photo_slot',
                'slot_index' => $i,
                'label' => "Foto {$i}",
                'x' => 8,
                'y' => 4.5 + ($i - 1) * 21.0,
                'width' => 84,
                'height' => 19.5,
                'border_radius' => 8,
                'border_width' => 0,
                'z_index' => 1,
            ]);
        }
        TemplateElement::create([
            'template_id' => $t4Pink->id,
            'type' => 'text',
            'content' => '{date} • BERRY PINK',
            'x' => 8,
            'y' => 92,
            'width' => 60,
            'height' => 4,
            'font_size' => 18,
            'font_color' => '#ec4899',
            'text_align' => 'left',
            'z_index' => 2,
        ]);
        TemplateElement::create([
            'template_id' => $t4Pink->id,
            'type' => 'qr_code',
            'label' => 'QR',
            'x' => 74,
            'y' => 89.5,
            'width' => 18,
            'height' => 8,
            'z_index' => 3,
        ]);

        // --- 2 FOTO STRIP PRESETS ---
        // 7. Strip 2 Foto - Polaroid Duo
        $t2Strip = Template::updateOrCreate(
            ['slug' => 'strip-setengah-4r-2-foto'],
            [
                'name' => 'Strip 2 Foto (Polaroid Duo White)',
                'description' => 'Ukuran setengah 4R (2x6") dengan 2 slot foto vertikal besar & leluasa.',
                'category' => 'Strip',
                'paper_size' => 'Strip 2x6',
                'width' => 600,
                'height' => 1800,
                'orientation' => 'portrait',
                'photo_count' => 2,
                'background_color' => '#ffffff',
                'frame_style' => 'strip',
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $t2Strip->elements()->delete();
        TemplateElement::create([
            'template_id' => $t2Strip->id,
            'type' => 'photo_slot',
            'slot_index' => 1,
            'label' => 'Foto 1',
            'x' => 8,
            'y' => 6,
            'width' => 84,
            'height' => 39,
            'border_radius' => 12,
            'border_width' => 0,
            'z_index' => 1,
        ]);
        TemplateElement::create([
            'template_id' => $t2Strip->id,
            'type' => 'photo_slot',
            'slot_index' => 2,
            'label' => 'Foto 2',
            'x' => 8,
            'y' => 48,
            'width' => 84,
            'height' => 39,
            'border_radius' => 12,
            'border_width' => 0,
            'z_index' => 1,
        ]);
        TemplateElement::create([
            'template_id' => $t2Strip->id,
            'type' => 'text',
            'content' => '{date} • POLAROID DUO',
            'x' => 8,
            'y' => 92,
            'width' => 60,
            'height' => 4,
            'font_size' => 18,
            'font_color' => '#94a3b8',
            'text_align' => 'left',
            'z_index' => 2,
        ]);
        TemplateElement::create([
            'template_id' => $t2Strip->id,
            'type' => 'qr_code',
            'label' => 'QR',
            'x' => 74,
            'y' => 89.5,
            'width' => 18,
            'height' => 8,
            'z_index' => 3,
        ]);

        // =========================================================================
        // B. FORMAT FULL (KERTAS 4R UTUH / 4x6" / 1200x1800 px)
        // =========================================================================

        // 4. Full 1 Foto (Single Portrait Studio)
        $t1Full = Template::updateOrCreate(
            ['slug' => 'full-4r-1-foto'],
            [
                'name' => 'Full 4R (1 Foto Portrait)',
                'description' => 'Ukuran kertas 4R utuh (4x6 inci / 1200x1800 px) dengan 1 foto potret besar mewah.',
                'category' => 'Full',
                'paper_size' => '4R',
                'width' => 1200,
                'height' => 1800,
                'orientation' => 'portrait',
                'photo_count' => 1,
                'background_color' => '#ffffff',
                'frame_style' => 'full',
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $t1Full->elements()->delete();

        TemplateElement::create([
            'template_id' => $t1Full->id,
            'type' => 'photo_slot',
            'slot_index' => 1,
            'label' => 'Foto 1',
            'x' => 8,
            'y' => 8,
            'width' => 84,
            'height' => 78,
            'border_radius' => 16,
            'border_width' => 0,
            'z_index' => 1,
        ]);

        TemplateElement::create([
            'template_id' => $t1Full->id,
            'type' => 'text',
            'content' => '{date} • STUDIO PORTRAIT',
            'x' => 8,
            'y' => 91,
            'width' => 60,
            'height' => 4,
            'font_size' => 20,
            'font_color' => '#64748b',
            'text_align' => 'left',
            'z_index' => 2,
        ]);

        TemplateElement::create([
            'template_id' => $t1Full->id,
            'type' => 'qr_code',
            'label' => 'QR',
            'x' => 74,
            'y' => 89,
            'width' => 18,
            'height' => 7.5,
            'z_index' => 3,
        ]);

        // 5. Full 2 Foto (Duet Portrait)
        $t2Full = Template::updateOrCreate(
            ['slug' => 'full-4r-2-foto'],
            [
                'name' => 'Full 4R (2 Foto Duet)',
                'description' => 'Ukuran kertas 4R utuh (4x6 inci / 1200x1800 px) dengan 2 slot foto atas bawah.',
                'category' => 'Full',
                'paper_size' => '4R',
                'width' => 1200,
                'height' => 1800,
                'orientation' => 'portrait',
                'photo_count' => 2,
                'background_color' => '#ffffff',
                'frame_style' => 'full',
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $t2Full->elements()->delete();

        TemplateElement::create([
            'template_id' => $t2Full->id,
            'type' => 'photo_slot',
            'slot_index' => 1,
            'label' => 'Foto 1',
            'x' => 10,
            'y' => 8,
            'width' => 80,
            'height' => 39,
            'border_radius' => 12,
            'border_width' => 0,
            'z_index' => 1,
        ]);

        TemplateElement::create([
            'template_id' => $t2Full->id,
            'type' => 'photo_slot',
            'slot_index' => 2,
            'label' => 'Foto 2',
            'x' => 10,
            'y' => 50,
            'width' => 80,
            'height' => 39,
            'border_radius' => 12,
            'border_width' => 0,
            'z_index' => 1,
        ]);

        TemplateElement::create([
            'template_id' => $t2Full->id,
            'type' => 'text',
            'content' => '{date} • DUET MEMORIES',
            'x' => 10,
            'y' => 92,
            'width' => 60,
            'height' => 4,
            'font_size' => 20,
            'font_color' => '#64748b',
            'text_align' => 'left',
            'z_index' => 2,
        ]);

        TemplateElement::create([
            'template_id' => $t2Full->id,
            'type' => 'qr_code',
            'label' => 'QR',
            'x' => 74,
            'y' => 90,
            'width' => 16,
            'height' => 7,
            'z_index' => 3,
        ]);

        // 6. Full 4 Foto (Grid 2x2 Kertas 4R)
        $t4Full = Template::updateOrCreate(
            ['slug' => 'full-4r-4-foto'],
            [
                'name' => 'Full 4R (4 Foto Grid 2x2)',
                'description' => 'Ukuran kertas 4R utuh (4x6 inci / 1200x1800 px) dengan 4 slot foto 2x2 simetris.',
                'category' => 'Full',
                'paper_size' => '4R',
                'width' => 1200,
                'height' => 1800,
                'orientation' => 'portrait',
                'photo_count' => 4,
                'background_color' => '#ffffff',
                'frame_style' => 'grid',
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $t4Full->elements()->delete();

        $coords4 = [
            ['x' => 6, 'y' => 9],
            ['x' => 52, 'y' => 9],
            ['x' => 6, 'y' => 50],
            ['x' => 52, 'y' => 50],
        ];
        foreach ($coords4 as $idx => $c) {
            TemplateElement::create([
                'template_id' => $t4Full->id,
                'type' => 'photo_slot',
                'slot_index' => $idx + 1,
                'label' => 'Foto ' . ($idx + 1),
                'x' => $c['x'],
                'y' => $c['y'],
                'width' => 42,
                'height' => 38,
                'border_radius' => 12,
                'border_width' => 0,
                'z_index' => 1,
            ]);
        }

        TemplateElement::create([
            'template_id' => $t4Full->id,
            'type' => 'text',
            'content' => '{date} • 4-GRID MOMENTS',
            'x' => 6,
            'y' => 91,
            'width' => 60,
            'height' => 4,
            'font_size' => 20,
            'font_color' => '#64748b',
            'text_align' => 'left',
            'z_index' => 2,
        ]);

        TemplateElement::create([
            'template_id' => $t4Full->id,
            'type' => 'qr_code',
            'label' => 'QR',
            'x' => 76,
            'y' => 89.5,
            'width' => 18,
            'height' => 7.5,
            'z_index' => 3,
        ]);

        // 7. Full 6 Foto (Grid 2x3 Mini)
        $t6Full = Template::updateOrCreate(
            ['slug' => 'full-4r-6-foto'],
            [
                'name' => 'Full 4R (6 Foto Grid 2x3)',
                'description' => 'Ukuran kertas 4R utuh (4x6 inci / 1200x1800 px) dengan 6 slot foto mini seru.',
                'category' => 'Full',
                'paper_size' => '4R',
                'width' => 1200,
                'height' => 1800,
                'orientation' => 'portrait',
                'photo_count' => 6,
                'background_color' => '#ffffff',
                'frame_style' => 'grid',
                'is_active' => true,
                'is_default' => false,
            ]
        );
        $t6Full->elements()->delete();

        $coords6 = [
            ['x' => 6, 'y' => 6.5],
            ['x' => 52, 'y' => 6.5],
            ['x' => 6, 'y' => 34.0],
            ['x' => 52, 'y' => 34.0],
            ['x' => 6, 'y' => 61.5],
            ['x' => 52, 'y' => 61.5],
        ];
        foreach ($coords6 as $idx => $c) {
            TemplateElement::create([
                'template_id' => $t6Full->id,
                'type' => 'photo_slot',
                'slot_index' => $idx + 1,
                'label' => 'Foto ' . ($idx + 1),
                'x' => $c['x'],
                'y' => $c['y'],
                'width' => 42,
                'height' => 25.5,
                'border_radius' => 8,
                'border_width' => 0,
                'z_index' => 1,
            ]);
        }

        TemplateElement::create([
            'template_id' => $t6Full->id,
            'type' => 'text',
            'content' => '{date} • 6-MINI MEMORIES',
            'x' => 6,
            'y' => 91,
            'width' => 60,
            'height' => 4,
            'font_size' => 18,
            'font_color' => '#64748b',
            'text_align' => 'left',
            'z_index' => 2,
        ]);

        TemplateElement::create([
            'template_id' => $t6Full->id,
            'type' => 'qr_code',
            'label' => 'QR',
            'x' => 76,
            'y' => 89.5,
            'width' => 18,
            'height' => 7.5,
            'z_index' => 3,
        ]);
    }
}
