<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportLinks;
use PHPUnit\Framework\TestCase;

final class AuctionReportLinksTest extends TestCase
{
    public function test_semantic_roles_and_telephone_destinations_have_bounded_hitboxes(): void
    {
        $definition = ['width' => 595.5, 'height' => 842.25, 'groups' => ['phone' => ['f1'], 'video_url' => ['f2']], 'fields' => ['f1' => ['x' => 50, 'y' => 100, 'width' => 90, 'sy' => 12], 'f2' => ['x' => 50, 'y' => 200, 'width' => 150, 'sy' => 14]], 'link_fields' => ['phone' => 'phone', 'video' => 'video_url']];
        $snapshot = ['marketing' => ['contacts' => ['phone' => '0534811800'], 'links' => [['label' => 'اسم حر', 'role' => 'video', 'url' => 'https://example.com/video']]]];
        $links = (new AuctionReportLinks)->forPage($snapshot, $definition);
        self::assertSame('tel:0534811800', $links['phone']['url']);
        self::assertSame('https://example.com/video', $links['video']['url']);
        self::assertSame([50.0, 88.0, 140.0, 103.0], $links['phone']['box']);
    }

    public function test_unsafe_or_missing_destinations_never_create_pdf_links(): void
    {
        foreach (['javascript:alert(1)', 'data:text/html,hello', 'file:///C:/secret', 'https://user:pass@example.com', null] as $value) {
            self::assertNull((new AuctionReportLinks)->safeUrl($value));
        }
        self::assertSame('https://example.com/x', (new AuctionReportLinks)->safeUrl('https://example.com/x'));
    }
}
