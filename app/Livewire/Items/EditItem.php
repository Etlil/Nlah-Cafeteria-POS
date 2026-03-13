<?php

namespace App\Livewire\Items;

use App\Models\Item;
use Livewire\Component;
use Livewire\WithFileUploads;
use Filament\Support\RawJs;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Forms\Components\ToggleButtons;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Illuminate\Support\Facades\Storage;

class EditItem extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use WithFileUploads;

    public Item $record;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Edit The Item')
                    ->description('Update the item details as you wish!')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Item Name')
                            ->required(),
                        Select::make('type')
                            ->label('Category')
                            ->options([
                                'meals' => 'Meals',
                                'drinks' => 'Drinks',
                                'snacks' => 'Snacks',
                            ])
                            ->default('meals')
                            ->required()
                            ->native(false),
                        TextInput::make('price')
                            ->prefix('PHP')
                            ->numeric()
                            ->dehydrateStateUsing(function ($state) {
                                $value = preg_replace('/[^\d.]/', '', (string) $state);
                                return $value === '' ? 0 : (int) floor((float) $value);
                            })
                            ->rules(['required', 'numeric', 'min:0']),
                        // Fixed Image Upload Field with WEBP support
                        FileUpload::make('image')
                            ->label('Item Image')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('item_images')
                            ->visibility('public')
                            ->maxSize(5120) // Increased to 5MB for WEBP
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'])
                            ->imagePreviewHeight('150')
                            ->loadingIndicatorPosition('left')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('left')
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull()
                            ->helperText('Upload a new image to replace the existing one (Max: 5MB, Formats: JPG, PNG, GIF, WEBP)')
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                // Generate a unique filename with timestamp
                                $originalName = $file->getClientOriginalName();
                                $extension = $file->getClientOriginalExtension();
                                $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                                $timestamp = time();
                                return "{$baseName}_{$timestamp}.{$extension}";
                            }),
                        ToggleButtons::make('status')
                            ->label('Is this item active?')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive'
                            ])
                            ->grouped()
                    ])
            ])
            ->statePath('data')
            ->model($this->record);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Handle image upload properly
        if (isset($data['image']) && !empty($data['image'])) {
            if (is_object($data['image']) && method_exists($data['image'], 'getClientOriginalName')) {
                $tempFile = $data['image'];

                $originalName = $tempFile->getClientOriginalName();
                $extension = $tempFile->getClientOriginalExtension();
                $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                $timestamp = time();
                $filename = "{$baseName}_{$timestamp}.{$extension}";

                $path = $tempFile->storeAs('item_images', $filename, 'public');
                $data['image'] = $path ?: null;
            } elseif (is_string($data['image'])) {
                $data['image'] = ltrim($data['image'], '/');
            }

            if ($this->record->image && $this->record->image !== $data['image']) {
                Storage::disk('public')->delete($this->record->image);
            }
        }

        // Update the record with all data
        $this->record->update($data);

        Notification::make()
            ->title('Item Updated')
            ->body("Item {$this->record->name} has been updated successfully")
            ->success()
            ->send();
            
        // Optional: Redirect back to list
        $this->redirect('/manage-items');
    }

    public function render(): View
    {
        return view('livewire.items.edit-item');
    }
}
