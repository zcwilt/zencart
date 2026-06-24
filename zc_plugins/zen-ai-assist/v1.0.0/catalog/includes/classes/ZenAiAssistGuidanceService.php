<?php

class ZenAiAssistGuidanceService
{
    private ?ZenAiAssistContentRegistry $registry = null;
    private string $guidanceDirectory = '';

    public function __construct(string|ZenAiAssistContentRegistry $guidanceDirectory)
    {
        if ($guidanceDirectory instanceof ZenAiAssistContentRegistry) {
            $this->registry = $guidanceDirectory;
            return;
        }

        $this->guidanceDirectory = rtrim($guidanceDirectory, '/\\') . '/';
    }

    public function listTopics(): array
    {
        $topics = [];

        foreach ($this->topicFiles() as $topicRecord) {
            $path = $topicRecord['path'];
            $slug = basename($path, '.md');
            $contents = @file($path, FILE_IGNORE_NEW_LINES);
            $title = $slug;

            if (is_array($contents) && isset($contents[0])) {
                $firstLine = trim((string)$contents[0]);
                if (str_starts_with($firstLine, '# ')) {
                    $title = trim(substr($firstLine, 2));
                }
            }

            $topics[] = [
                'topic' => $slug,
                'title' => $title,
                'path' => $path,
                'source' => $topicRecord['source'] ?? null,
            ];
        }

        usort($topics, static function (array $left, array $right): int {
            return [(string)$left['title'], (string)$left['topic']] <=> [(string)$right['title'], (string)$right['topic']];
        });

        return $topics;
    }

    public function readTopic(string $topic): array
    {
        $topic = trim($topic);
        if ($topic === '') {
            return [
                'topic' => $topic,
                'found' => false,
                'message' => 'Topic is required.',
            ];
        }

        $topicRecord = $this->findTopicRecord($topic);
        if ($topicRecord === null) {
            return [
                'topic' => $topic,
                'found' => false,
                'message' => 'Guidance topic not found.',
            ];
        }

        $path = $topicRecord['path'];
        $contents = @file_get_contents($path);
        if (!is_string($contents)) {
            return [
                'topic' => $topic,
                'found' => false,
                'message' => 'Guidance topic could not be read.',
            ];
        }

        return [
            'topic' => $topic,
            'found' => true,
            'path' => $path,
            'source' => $topicRecord['source'] ?? null,
            'content' => $contents,
        ];
    }

    private function topicFiles(): array
    {
        $records = [];
        $seenTopics = [];

        foreach ($this->guidanceSources() as $source) {
            $guidanceDirectory = rtrim((string)($source['guidance_dir'] ?? ''), '/\\') . '/';
            if (!is_dir($guidanceDirectory)) {
                continue;
            }

            foreach (glob($guidanceDirectory . '*.md') ?: [] as $path) {
                $topic = basename($path, '.md');
                if (isset($seenTopics[$topic])) {
                    continue;
                }

                $seenTopics[$topic] = true;
                $records[] = [
                    'topic' => $topic,
                    'path' => $path,
                    'source' => $this->sourceSummary($source),
                ];
            }
        }

        usort($records, static function (array $left, array $right): int {
            return [(string)$left['topic'], (string)$left['path']] <=> [(string)$right['topic'], (string)$right['path']];
        });

        return $records;
    }

    private function findTopicRecord(string $topic): ?array
    {
        foreach ($this->topicFiles() as $topicRecord) {
            if (($topicRecord['topic'] ?? '') === $topic) {
                return $topicRecord;
            }
        }

        return null;
    }

    private function guidanceSources(): array
    {
        if ($this->registry !== null) {
            return $this->registry->guidanceSources();
        }

        if ($this->guidanceDirectory === '') {
            return [];
        }

        return [[
            'id' => 'bundled',
            'type' => 'bundled',
            'label' => 'Zen AI Assist Bundled',
            'guidance_dir' => $this->guidanceDirectory,
        ]];
    }

    private function sourceSummary(array $source): array
    {
        return [
            'id' => (string)($source['id'] ?? ''),
            'type' => (string)($source['type'] ?? ''),
            'label' => (string)($source['label'] ?? ''),
            'plugin' => is_array($source['plugin'] ?? null) ? $source['plugin'] : null,
        ];
    }
}
