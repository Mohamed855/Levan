<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Actions\Tenants\AuctionEstablishments\SaveAuctionEstablishmentRequestAction;
use App\Http\Requests\Tenants\AuctionEstablishments\SaveAuctionEstablishmentRequest;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

final class AuctionEstablishmentAttachmentsTest extends TestCase
{
    public function test_valuation_is_always_required_even_when_entry_amount_is_zero(): void
    {
        $data = $this->attachmentData(entryAmount: 0);
        $this->expectException(ValidationException::class);
        $this->storeAttachments($data, [], ['valuation' => [42 => true]]);
    }

    public function test_replacing_valuation_preserves_old_file_on_disk_until_after_commit(): void
    {
        $data = $this->attachmentData(entryAmount: 0);
        $this->storeAttachments($data, ['valuation' => [42 => UploadedFile::fake()->image('new-valuation.png')]], ['valuation' => [42 => true]]);
        self::assertSame('new-valuation.png', $data['attachments']['valuation'][42]['name']);
        Storage::disk('tenant_private')->assertExists('valuation.png');
        Storage::disk('tenant_private')->assertExists($data['attachments']['valuation'][42]['path']);
    }

    public function test_removing_a_required_valuation_requires_a_replacement(): void
    {
        $data = $this->attachmentData(entryAmount: 5000);
        $this->expectException(ValidationException::class);
        $this->storeAttachments($data, [], ['valuation' => [42 => true]]);
    }

    public function test_replacement_satisfies_a_removed_required_valuation(): void
    {
        $data = $this->attachmentData(entryAmount: 5000);
        $this->storeAttachments($data, ['valuation' => [42 => UploadedFile::fake()->image('replacement.png')]], ['valuation' => [42 => true]]);
        self::assertSame('replacement.png', $data['attachments']['valuation'][42]['name']);
        Storage::disk('tenant_private')->assertExists($data['attachments']['valuation'][42]['path']);
    }

    public function test_removal_cannot_target_another_property(): void
    {
        $data = $this->attachmentData(entryAmount: 0);
        $this->expectException(ValidationException::class);
        $this->storeAttachments($data, [], ['valuation' => [43 => true]]);
    }

    public function test_a_removed_certificate_is_required_again(): void
    {
        $auction = new Auction;
        $auction->setRelation('establishmentRequest', new AuctionEstablishmentRequest(['data' => ['attachments' => ['manager_certificate' => [0 => ['path' => 'manager.png']]]]]));
        $request = new SaveAuctionEstablishmentRequest;
        $request->merge(['remove_attachments_manager_certificate' => '1']);
        $rules = $request->rules($auction);
        $validator = Validator::make([], ['attachments.manager_certificate' => $rules['attachments.manager_certificate']]);
        self::assertTrue($validator->fails());
        self::assertTrue($validator->errors()->has('attachments.manager_certificate'));
    }

    private function attachmentData(int $entryAmount = 0): array
    {
        Storage::fake('tenant_private');
        $attachments = [];
        foreach (['manager_certificate', 'auctioneer_certificate', 'valuation'] as $type) {
            Storage::disk('tenant_private')->put($type.'.png', 'image');
            $attachments[$type][$type === 'valuation' ? 42 : 0] = ['path' => $type.'.png', 'name' => $type.'.png', 'mime' => 'image/png'];
        }

        return ['properties' => [['property_id' => 42, 'entry_amount' => $entryAmount, 'agent_name' => '']], 'attachments' => $attachments];
    }

    private function storeAttachments(array &$data, array $uploads, array $removals): void
    {
        $auction = new Auction;
        $auction->id = 1;
        $storedPaths = [];
        $method = new ReflectionMethod(SaveAuctionEstablishmentRequestAction::class, 'storeAttachments');
        $method->invokeArgs(new SaveAuctionEstablishmentRequestAction, [$auction, &$data, $uploads, &$storedPaths, $removals]);
    }
}
