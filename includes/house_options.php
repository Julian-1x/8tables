<?php

function parseBlockLot(string $houseNumber): array
{
    $block = null;
    $lot = null;

    if (preg_match('/(?:block|blk)\s*([0-9]+)/i', $houseNumber, $blockMatch)) {
        $block = (int) $blockMatch[1];
    }

    if (preg_match('/(?:lot)\s*([0-9]+)/i', $houseNumber, $lotMatch)) {
        $lot = (int) $lotMatch[1];
    } elseif (preg_match('/\b([0-9]+)\b/', $houseNumber, $numberMatch)) {
        $lot = (int) $numberMatch[1];
    }

    return [
        'block' => $block,
        'lot' => $lot
    ];
}

function groupHousesForDropdown(array $houses): array
{
    $groups = [];

    foreach ($houses as $house) {
        $houseNumber = (string) ($house['house_number'] ?? '');
        $ownerName = trim((string) ($house['owner_name'] ?? ''));
        $parsed = parseBlockLot($houseNumber);

        $hasBlock = $parsed['block'] !== null;
        $blockLabel = $hasBlock ? ('Block ' . str_pad((string) $parsed['block'], 2, '0', STR_PAD_LEFT)) : 'Other';
        $blockSort = $hasBlock ? (int) $parsed['block'] : 9999;
        $lotSort = $parsed['lot'] ?? 9999;

        $optionLabel = $houseNumber;
        if ($parsed['lot'] !== null) {
            $optionLabel = 'Lot ' . str_pad((string) $parsed['lot'], 2, '0', STR_PAD_LEFT) . ' - ' . $houseNumber;
        }
        if ($ownerName !== '') {
            $optionLabel .= ' (' . $ownerName . ')';
        }

        $groupKey = (string) $blockSort;
        if (!isset($groups[$groupKey])) {
            $groups[$groupKey] = [
                'label' => $blockLabel,
                'sort' => $blockSort,
                'items' => []
            ];
        }

        $house['_dropdown_label'] = $optionLabel;
        $house['_lot_sort'] = $lotSort;
        $groups[$groupKey]['items'][] = $house;
    }

    usort($groups, static function ($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });

    foreach ($groups as &$group) {
        usort($group['items'], static function ($a, $b) {
            if ($a['_lot_sort'] === $b['_lot_sort']) {
                return strcmp((string) ($a['house_number'] ?? ''), (string) ($b['house_number'] ?? ''));
            }
            return $a['_lot_sort'] <=> $b['_lot_sort'];
        });
    }
    unset($group);

    return $groups;
}

function renderHouseOptions(array $houseGroups, $selectedHouseId = null): void
{
    $selected = (string) ($selectedHouseId ?? '');

    foreach ($houseGroups as $group) {
        echo '<optgroup label="' . htmlspecialchars((string) $group['label']) . '">';

        foreach ($group['items'] as $house) {
            $houseId = (string) $house['id'];
            $isSelected = $selected !== '' && $selected === $houseId ? ' selected' : '';
            $label = (string) ($house['_dropdown_label'] ?? $house['house_number'] ?? ('House ' . $houseId));

            echo '<option value="' . htmlspecialchars($houseId) . '"' . $isSelected . '>' .
                htmlspecialchars($label) . '</option>';
        }

        echo '</optgroup>';
    }
}

