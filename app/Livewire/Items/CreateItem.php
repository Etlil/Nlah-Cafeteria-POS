<?php

namespace App\Livewire\Items;

use App\Models\Item;
use Livewire\Component;
use Livewire\WithFileUploads;
use Filament\Support\RawJs;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Forms\Components\ToggleButtons;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;

class CreateItem extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use WithFileUploads;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Add the Item')
                    ->description('fill the form to add new item')
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
                            ->live(onBlur: false)
                            ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 2, '.', ',') : '')
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
                        // Fixed Image Upload Field
                        FileUpload::make('image')
                            ->label('Item Image')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('item_images')
                            ->visibility('public')
                            ->maxSize(5120)
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
                            ->helperText('Upload an image for the item (Max: 5MB, Formats: JPG, PNG, GIF, WEBP)')
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(function ($file) {
                                // Generate a unique filename with timestamp
                                $originalName = $file->getClientOriginalName();
                                $extension = $file->getClientOriginalExtension();
                                $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                                $timestamp = time();
                                $filename = "{$baseName}_{$timestamp}.{$extension}";
                                return $filename;
                            }),
                        ToggleButtons::make('status')
                            ->label('Is this Item Active?')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'In Active',
                            ])
                            ->default('active')
                            ->grouped()
                    ])
            ])
            ->statePath('data')
            ->model(Item::class);
    }

    public function create(): void
    {
        // Get the form data
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
                // Filament already stored the file; keep the relative path.
                $data['image'] = ltrim($data['image'], '/');
            }
        } else {
            $data['image'] = null;
        }

        // Create the record with all data
        $record = Item::create($data);

        Notification::make()
            ->title('Item Created!')
            ->success()
            ->body("Item created successfully!")
            ->send();
        
        // Redirect to the manage items page
        $this->redirect('/manage-items');
    }

    public function render(): View
    {
        return view('livewire.items.create-item');
    }
}
