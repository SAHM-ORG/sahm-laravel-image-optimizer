<?php

namespace SAHM\ImageOptimizer\Console\Commands;

use Illuminate\Console\Command;
use SAHM\ImageOptimizer\Facades\ImageOptimizer;

class InfoCommand extends Command
{
    protected $signature = 'image-optimizer:info';

    protected $description = 'Display image optimizer system information';

    public function handle(): int
    {
        $this->info('SAHM Laravel Image Optimizer - System Information');
        $this->newLine();

        // Processor Info
        $processorInfo = ImageOptimizer::getProcessorInfo();
        
        $this->info('Available Processors:');
        $this->table(
            ['Processor', 'Available', 'Active', 'Formats'],
            collect($processorInfo)->map(function ($info, $name) {
                return [
                    $name,
                    $info['available'] ? '✓' : '✗',
                    $info['active'] ? '✓' : '',
                    implode(', ', $info['supported_formats']),
                ];
            })->toArray()
        );

        $this->newLine();

        // Configuration
        $config = config('image-optimizer');
        
        $this->info('Configuration:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Storage Disk', $config['storage']['disk']],
                ['Base Path', $config['storage']['base_path']],
                ['Default Quality', $config['processing']['default_quality']],
                ['Output Format', $config['formats']['output']],
                ['Responsive Sizes', implode(', ', $config['processing']['sizes']) . 'px'],
                ['Max Dimensions', "{$config['processing']['max_width']}x{$config['processing']['max_height']}"],
                ['Blur Placeholder', $config['processing']['blur_placeholder']['enabled'] ? 'Enabled' : 'Disabled'],
                ['Queue Enabled', $config['queue']['enabled'] ? 'Yes' : 'No'],
                ['Cache Enabled', $config['cache']['enabled'] ? 'Yes' : 'No'],
            ]
        );

        $this->newLine();

        // PHP Extensions
        $this->info('PHP Extensions:');
        $this->table(
            ['Extension', 'Status'],
            [
                ['Imagick', extension_loaded('imagick') ? '✓ Installed' : '✗ Not installed'],
                ['GD', extension_loaded('gd') ? '✓ Installed' : '✗ Not installed'],
                ['EXIF', extension_loaded('exif') ? '✓ Installed' : '✗ Not installed'],
                ['Fileinfo', extension_loaded('fileinfo') ? '✓ Installed' : '✗ Not installed'],
            ]
        );

        return self::SUCCESS;
    }
}
