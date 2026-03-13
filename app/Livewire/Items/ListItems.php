<?php

namespace App\Livewire\Items;

use App\Models\Item;
use Livewire\Component;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Contracts\HasActions;

use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;

class ListItems extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    public $category = 'all';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Item::query()
                ->when($this->category !== 'all', function (Builder $query) {
                    $query->where('type', $this->category);
                }))
            ->searchable()
            ->columns([
                // New Image Column
                ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->getStateUsing(function (?Item $record): ?string {
                        if (!$record || !$record->image) {
                            return null;
                        }

                        // Normalize to a storage-relative path for the public disk.
                        $path = str_starts_with($record->image, 'item_images/')
                            ? $record->image
                            : 'item_images/' . ltrim($record->image, '/');

                        // Build an absolute URL using the current request host/port.
                        return rtrim(request()->getSchemeAndHttpHost(), '/') . '/storage/' . $path;
                    })
                    ->size(60)
                    ->circular()
                    ->defaultImageUrl(rtrim(request()->getSchemeAndHttpHost(), '/') . '/images/placeholder.png')
                    ->extraAttributes(['class' => 'p-2']),
                    
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('price')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'PHP ' . number_format($state, 2, '.', ','))
                    ->searchable(),
                    
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                    })
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Add filters if needed
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Add New Item')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => route('items.create'))
                    ->color('success')
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (Item $record): string => route('items.update', $record))
                    ->color('warning'),
                    
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (Item $record) {
                        // Delete image file if exists
                        if ($record->image) {
                            $path = str_starts_with($record->image, 'item_images/')
                                ? $record->image
                                : 'item_images/' . ltrim($record->image, '/');
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                        }
                        $record->delete();
                    })
                    ->successNotification(
                        Notification::make()
                            ->title('Item deleted successfully')
                            ->success()
                    ),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Action::make('delete')
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                // Delete image file if exists
                                if ($record->image) {
                                    $path = str_starts_with($record->image, 'item_images/')
                                        ? $record->image
                                        : 'item_images/' . ltrim($record->image, '/');
                                    \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
                                }
                                $record->delete();
                            }
                        })
                        ->successNotification(
                            Notification::make()
                                ->title('Selected items deleted successfully')
                                ->success()
                        ),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.items.list-items');
    }
}
