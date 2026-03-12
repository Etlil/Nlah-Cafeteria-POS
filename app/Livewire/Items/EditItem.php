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
                        TextInput::make('price')
                            ->prefix('PHP')
                            ->live(onBlur: false)
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, '.', ',') : '')
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state && is_string($state)) {
                                    $cleanValue = preg_replace('/[^\d]/', '', $state);
                                    if ($cleanValue && $cleanValue !== $state) {
                                        // Store as integer (multiplied by 100)
                                        $set('price', (int) $cleanValue);
                                    }
                                }
                            })
                            ->extraAttributes([
                                'x-data' => "{ 
                                    formatNum(val) { 
                                        let str = val.toString();
                                        // Format with thousand separators and two decimal places
                                        let num = parseInt(str.replace(/[^\\d]/g, '')) / 100;
                                        return num.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                    } 
                                }",
                                'x-on:input' => "let clean = \$event.target.value.replace(/[^\\d]/g, ''); 
                                                  if(clean) { 
                                                      let num = parseInt(clean);
                                                      let formatted = (num / 100).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                                      \$event.target.value = formatted; 
                                                      \$wire.\$set('data.price', num);
                                                  } else { 
                                                      \$event.target.value = ''; 
                                                      \$wire.\$set('data.price', ''); 
                                                  }",
                                'x-on:keypress' => "if(!/[0-9]/.test(\$event.key) && !['Backspace','Delete','Tab','ArrowLeft','ArrowRight'].includes(\$event.key)) \$event.preventDefault();",
                            ])
                            ->dehydrateStateUsing(fn ($state) => (int) $state)
                            ->rules(['required', 'integer', 'min:0']),
                        // Fixed Image Upload Field with WEBP support
                        FileUpload::make('image')
                            ->label('Item Image')
                            ->image()
                            ->imageEditor()
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
        if (isset($data['image'])) {
            // If it's a new uploaded file (Livewire temporary file)
            if (is_object($data['image']) && method_exists($data['image'], 'getFilename')) {
                // Get the stored filename from the temporary file
                $filename = $data['image']->getFilename();
                
                // The file is already stored in the 'item_images' directory
                // We just need to save the filename to the database
                $data['image'] = $filename;
                
                // Delete old image if it exists and is different
                if ($this->record->image && $this->record->image !== $filename) {
                    Storage::disk('public')->delete('item_images/' . $this->record->image);
                }
            }
            // If it's already a string (existing filename), keep it as is
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