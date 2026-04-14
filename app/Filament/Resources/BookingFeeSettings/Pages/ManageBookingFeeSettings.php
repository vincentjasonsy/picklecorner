<?php

namespace App\Filament\Resources\BookingFeeSettings\Pages;

use App\Filament\Resources\BookingFeeSettings\BookingFeeSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBookingFeeSettings extends ManageRecords
{
    protected static string $resource = BookingFeeSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
