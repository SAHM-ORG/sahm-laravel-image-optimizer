<?php

namespace SAHM\ImageOptimizer\Processors;

use SAHM\ImageOptimizer\Processors\Contracts\ProcessorInterface;
use SAHM\ImageOptimizer\Exceptions\ProcessorNotFoundException;

class ProcessorManager
{
    private ?ProcessorInterface $processor = null;
    private array $processors = [];

    public function __construct()
    {
        $this->registerProcessors();
        $this->detectProcessor();
    }

    /**
     * Get active processor
     */
    public function getProcessor(): ProcessorInterface
    {
        if ($this->processor === null) {
            throw new ProcessorNotFoundException('No image processor available');
        }

        return $this->processor;
    }

    /**
     * Get processor by name
     */
    public function getProcessorByName(string $name): ProcessorInterface
    {
        $name = strtolower($name);

        if (!isset($this->processors[$name])) {
            throw new ProcessorNotFoundException("Processor '{$name}' not found");
        }

        if (!$this->processors[$name]->isAvailable()) {
            throw new ProcessorNotFoundException("Processor '{$name}' is not available");
        }

        return $this->processors[$name];
    }

    /**
     * Check if processor is available
     */
    public function hasProcessor(string $name): bool
    {
        $name = strtolower($name);

        return isset($this->processors[$name]) && $this->processors[$name]->isAvailable();
    }

    /**
     * Get all available processors
     */
    public function getAvailableProcessors(): array
    {
        return array_filter($this->processors, fn($processor) => $processor->isAvailable());
    }

    /**
     * Get processor information
     */
    public function getInfo(): array
    {
        $info = [];

        foreach ($this->processors as $name => $processor) {
            $info[$name] = [
                'available' => $processor->isAvailable(),
                'active' => $this->processor && $this->processor->getName() === $name,
                'supported_formats' => $processor->isAvailable() ? $processor->getSupportedFormats() : [],
            ];
        }

        return $info;
    }

    /**
     * Register all processors
     */
    private function registerProcessors(): void
    {
        $this->processors['imagick'] = new ImagickProcessor();
        $this->processors['gd'] = new GdProcessor();
    }

    /**
     * Auto-detect and set the best available processor
     */
    private function detectProcessor(): void
    {
        $preferred = config('image-optimizer.processing.processor', 'imagick');

        // Try preferred processor first
        if ($this->hasProcessor($preferred)) {
            $this->processor = $this->processors[$preferred];
            return;
        }

        // Try fallback
        if (config('image-optimizer.processing.fallback', true)) {
            foreach ($this->processors as $processor) {
                if ($processor->isAvailable()) {
                    $this->processor = $processor;
                    return;
                }
            }
        }

        // No processor available
        $this->processor = null;
    }
}
