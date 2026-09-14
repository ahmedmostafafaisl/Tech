<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Dompdf\Dompdf;
use Dompdf\Options;

class LoadFonts extends Command
{
    protected $signature   = 'pdf:fonts';
    protected $description = 'Register Arabic fonts for dompdf';

    public function handle(): void
    {
        $options = new Options();
        $options->setChroot(base_path());
        $options->setFontDir(storage_path('fonts/'));
        $options->setFontCache(storage_path('fonts/'));

        $dompdf      = new Dompdf($options);
        $fontMetrics = $dompdf->getFontMetrics();

        $fontFile = storage_path('fonts/Cairo-Regular.ttf');

        // ✅ سجّل نفس الملف على كل الأوزان عشان dompdf ما يطلعش error
        $variants = [
            ['family' => 'cairo', 'style' => 'normal', 'weight' => 'normal'],
            ['family' => 'cairo', 'style' => 'normal', 'weight' => 'bold'],
            ['family' => 'cairo', 'style' => 'italic', 'weight' => 'normal'],
            ['family' => 'cairo', 'style' => 'italic', 'weight' => 'bold'],
        ];

        foreach ($variants as $variant) {
            $fontMetrics->registerFont($variant, $fontFile);
            $this->info("Registered: cairo [{$variant['style']}/{$variant['weight']}]");
        }

        $this->info('✅ All Cairo font variants registered successfully!');
    }
}
