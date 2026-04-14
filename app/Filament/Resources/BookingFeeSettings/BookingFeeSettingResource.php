<?php

namespace App\Filament\Resources\BookingFeeSettings;

use App\Filament\Resources\BookingFeeSettings\Pages\ManageBookingFeeSettings;
use App\Models\BookingFeeSetting;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class BookingFeeSettingResource extends Resource
{
    protected static ?string $model = BookingFeeSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return 'Booking fee setting';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Booking fee settings';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('base_fee')
                    ->label('Base fee (PHP)')
                    ->numeric()
                    ->default(BookingFeeSetting::DEFAULT_BASE_FEE)
                    ->required()
                    ->step('0.01'),
                TextInput::make('percentage_fee')
                    ->label('Percentage fee')
                    ->numeric()
                    ->default(BookingFeeSetting::DEFAULT_PERCENTAGE_FEE)
                    ->required()
                    ->step('0.0001')
                    ->helperText('Example: 0.02 is 2% of the courts subtotal.'),
                TextInput::make('max_fee')
                    ->label('Maximum fee cap (PHP)')
                    ->numeric()
                    ->default(BookingFeeSetting::DEFAULT_MAX_FEE)
                    ->step('0.01')
                    ->helperText('Leave empty for no maximum cap.')
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state === null || $state === '' ? null : $state),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Only one setting can be active. Saving as active will deactivate the others.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('base_fee')
                    ->label('Base (PHP)')
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('percentage_fee')
                    ->label('Percent')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state * 100, 2).'%'),
                TextColumn::make('max_fee')
                    ->label('Max (PHP)')
                    ->formatStateUsing(fn ($state): string => $state === null ? '—' : number_format((float) $state, 2)),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBookingFeeSettings::route('/'),
        ];
    }
}
