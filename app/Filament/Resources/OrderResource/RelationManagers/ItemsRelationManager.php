<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        // Form is not strictly needed if we don't allow creating/editing items directly here
        return $form
            ->schema([
                Forms\Components\TextInput::make('product_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->integer(),
                Forms\Components\TextInput::make('price_per_unit')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('sub_total')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product_name'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('price_per_unit')
                    ->money('usd'),
                Tables\Columns\TextColumn::make('sub_total')
                    ->money('usd'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(), // Disable creation of items directly here
            ])
            ->actions([
                // Tables\Actions\EditAction::make(), // Disable editing of items directly here
                // Tables\Actions\DeleteAction::make(), // Disable deletion
            ])
            ->bulkActions([
                /*Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),*/ // Disable bulk deletion
            ]);
    }
}
