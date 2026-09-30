<?php

namespace App\Services;

use InvalidArgumentException;

class TiptapImporter
{
    /**
     * Convert plain text or a limited Markdown subset to Tiptap document JSON.
     *
     * @return array{type: 'doc', content: list<array<string, mixed>>}
     */
    public function fromText(string $text, string $ext): array
    {
        $extension = strtolower(ltrim($ext, '.'));

        if (! in_array($extension, ['txt', 'md'], true)) {
            throw new InvalidArgumentException("Unsupported document extension: {$ext}");
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $blocks = [];
        $paragraphLines = [];
        $listType = null;
        $listItems = [];

        foreach (explode("\n", $text) as $line) {
            if (trim($line) === '') {
                $this->flushParagraph($blocks, $paragraphLines);
                $this->flushList($blocks, $listType, $listItems);

                continue;
            }

            if ($extension === 'md' && preg_match('/^(#{1,3})\s+(.+)$/u', $line, $heading) === 1) {
                $this->flushParagraph($blocks, $paragraphLines);
                $this->flushList($blocks, $listType, $listItems);
                $blocks[] = [
                    'type' => 'heading',
                    'attrs' => ['level' => strlen($heading[1])],
                    'content' => $this->textContent(trim($heading[2])),
                ];

                continue;
            }

            $itemType = null;
            $itemText = null;

            if ($extension === 'md' && preg_match('/^[-*] (.+)$/u', $line, $bullet) === 1) {
                $itemType = 'bulletList';
                $itemText = trim($bullet[1]);
            } elseif ($extension === 'md' && preg_match('/^\d+\. (.+)$/u', $line, $ordered) === 1) {
                $itemType = 'orderedList';
                $itemText = trim($ordered[1]);
            }

            if ($itemType !== null) {
                $this->flushParagraph($blocks, $paragraphLines);

                if ($itemType !== $listType) {
                    $this->flushList($blocks, $listType, $listItems);
                    $listType = $itemType;
                }

                $listItems[] = [
                    'type' => 'listItem',
                    'content' => [[
                        'type' => 'paragraph',
                        'content' => $this->textContent($itemText),
                    ]],
                ];

                continue;
            }

            $this->flushList($blocks, $listType, $listItems);
            $paragraphLines[] = $line;
        }

        $this->flushParagraph($blocks, $paragraphLines);
        $this->flushList($blocks, $listType, $listItems);

        if ($blocks === []) {
            $blocks[] = ['type' => 'paragraph'];
        }

        return [
            'type' => 'doc',
            'content' => array_values($blocks),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<int, string>  $lines
     */
    private function flushParagraph(array &$blocks, array &$lines): void
    {
        if ($lines === []) {
            return;
        }

        $text = trim(implode("\n", $lines));
        $blocks[] = [
            'type' => 'paragraph',
            'content' => $this->textContent($text),
        ];
        $lines = [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<int, array<string, mixed>>  $items
     *
     * @param-out null $type
     */
    private function flushList(array &$blocks, ?string &$type, array &$items): void
    {
        if ($type === null) {
            return;
        }

        $list = ['type' => $type];
        if ($type === 'orderedList') {
            $list['attrs'] = ['start' => 1];
        }

        $list['content'] = $items;
        $blocks[] = $list;
        $type = null;
        $items = [];
    }

    /**
     * @return array<int, array{type: 'text', text: string}>
     */
    private function textContent(string $text): array
    {
        return $text === '' ? [] : [['type' => 'text', 'text' => $text]];
    }
}
