<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\TemplateElement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class TemplateController extends Controller
{
    public function index(): Response
    {
        $templates = Template::with('elements')->get();
        return Inertia::render('Admin/Templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function builder(Request $request): Response
    {
        $template = null;
        if ($request->has('id')) {
            $template = Template::with('elements')->find($request->input('id'));
        }

        return Inertia::render('Admin/Templates/Builder', [
            'template' => $template,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $name = $request->input('name', 'Template Kustom');
        $paperSize = $request->input('paper_size', '4R');
        $orientation = $request->input('orientation', 'portrait');
        $bgColor = $request->input('background_color', '#ffffff');
        $photoCount = (int)$request->input('photo_count', 3);
        $elements = $request->input('elements', []);
        $overlayImage = $request->input('overlay_image');

        if ($id) {
            $template = Template::findOrFail($id);
            $template->update([
                'name' => $name,
                'paper_size' => $paperSize,
                'orientation' => $orientation,
                'background_color' => $bgColor,
                'photo_count' => $photoCount,
                'overlay_image' => $overlayImage,
            ]);
            $template->elements()->delete();
        } else {
            $template = Template::create([
                'name' => $name,
                'slug' => Str::slug($name) . '-' . rand(100, 999),
                'paper_size' => $paperSize,
                'orientation' => $orientation,
                'background_color' => $bgColor,
                'photo_count' => $photoCount,
                'overlay_image' => $overlayImage,
                'is_active' => true,
            ]);
        }

        foreach ($elements as $idx => $el) {
            TemplateElement::create([
                'template_id' => $template->id,
                'type' => $el['type'] ?? 'photo_slot',
                'slot_index' => $el['slot_index'] ?? null,
                'content' => $el['content'] ?? null,
                'x' => (float)($el['x'] ?? 0),
                'y' => (float)($el['y'] ?? 0),
                'width' => (float)($el['width'] ?? 100),
                'height' => (float)($el['height'] ?? 100),
                'z_index' => (int)($el['z_index'] ?? ($idx + 1)),
                'border_width' => (int)($el['border_width'] ?? 0),
                'border_color' => $el['border_color'] ?? null,
                'border_radius' => (int)($el['border_radius'] ?? 0),
                'font_size' => (int)($el['font_size'] ?? 24),
                'font_color' => $el['font_color'] ?? '#000000',
                'font_weight' => $el['font_weight'] ?? 'normal',
                'text_align' => $el['text_align'] ?? 'center',
            ]);
        }

        return response()->json(['success' => true, 'template' => $template->load('elements')]);
    }
}