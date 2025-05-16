<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 2; // Example sort order

    public static function form(Form $form): Form
    {
        // Basic form, will be enhanced for viewing details and status updates
        // For now, primarily for the structure. Direct creation of complex orders in admin is low priority.
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required()
                    ->disabledOn('edit'), // Don't change customer on edit
                Forms\Components\TextInput::make('order_total')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->disabled(), // Calculated field, disable for now
                Forms\Components\Select::make('status')
                    ->options([
                        'new' => 'New',
                        'processing' => 'Processing',
                        'paid' => 'Paid',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('shipping_address')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('billing_address')
                    ->columnSpanFull(),
                // Order items will be shown in a Repeater or RelationManager on the View/Edit page
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_total')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'gray',
                        'processing' => 'warning',
                        'paid' => 'success',
                        'shipped' => 'info',
                        'delivered' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'processing' => 'Processing',
                        'paid' => 'Paid',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'refunded' => 'Refunded',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(), // For status changes primarily
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'), // We might disable this later
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'view' => Pages\ViewOrder::route('/{record}'), // Add View page
        ];
    }
}
