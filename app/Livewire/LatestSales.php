<?php

namespace App\Livewire;

use App\Models\Sale;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;

class LatestSales extends TableWidget
{
    public bool $todayActive = false;
    public bool $monthActive = false;

    public string|null $activePreset = 'this_month';
    public function mount(): void
    {
        $this->applyThisMonth();
    }

    protected function applyToday(): void
    {
        $this->activePreset = 'today';

        $this->tableFilters = [
            'transaction_time' => [
                'from' => today()->toDateString(),
                'until' => today()->toDateString(),
            ],
        ];
    }

    protected function applyThisMonth(): void
    {
        $this->activePreset = 'this_month';

        $this->tableFilters = [
            'transaction_time' => [
                'from' => now()->startOfMonth()->toDateString(),
                'until' => now()->endOfMonth()->toDateString(),
            ],
        ];
    }

    protected function applyLastMonth(): void
    {
        $this->activePreset = 'last_month';

        $this->tableFilters = [
            'transaction_time' => [
                'from' => now()->subMonth()->startOfMonth()->toDateString(),
                'until' => now()->subMonth()->endOfMonth()->toDateString(),
            ],
        ];
    }

    protected function applyLast30Days(): void
    {
        $this->activePreset = 'last_30_days';

        $this->tableFilters = [
            'transaction_time' => [
                'from' => now()->subDays(29)->toDateString(),
                'until' => today()->toDateString(),
            ],
        ];
    }

    protected function clearPreset(): void
    {
        $this->activePreset = null;
        $this->tableFilters = [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Sale::query()->with(['customer','saleItems']))
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('customer.name')
                    ->sortable(),
                TextColumn::make('saleItems.item.name')
                    ->label('Sold Items')
                    ->bulleted()
                    ->limitList(2)
                    ->expandableLimitedList(),
                TextColumn::make('total')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'PHP ' . number_format($state, 0, ',', '.')),
                TextColumn::make('discount')
                    ->formatStateUsing(fn ($state) => 'PHP ' . number_format($state, 0, ',', '.')),
                TextColumn::make('paid_amount')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'PHP ' . number_format($state, 0, ',', '.')),
                TextColumn::make('paymentMethod.name'),
                TextColumn::make('created_at')
                    ->label('Transaction Time')
                    ->dateTime('d M Y')
                    ->description(fn ($record) => $record->created_at->diffForHumans())
                    ->sortable(),

            ])
            ->filters([
                Filter::make('transaction_time')
                    ->label('Transaction Time')
                    ->form([
                        DatePicker::make('from')->native(false),
                        DatePicker::make('until')->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                data_get($data, 'from'),
                                fn ($q, $from) =>
                                    $q->whereDate('created_at', '>=', $from)
                            )
                            ->when(
                                data_get($data, 'until'),
                                fn ($q, $until) =>
                                    $q->whereDate('created_at', '<=', $until)
                            );
                    })
,
            ])
            ->headerActions([
                Action::make('today')
                    ->label('Today')
                    ->icon('heroicon-o-calendar-days')
                    ->color(fn () => $this->activePreset === 'today' ? 'primary' : 'gray')
                    ->action(fn () =>
                        $this->activePreset === 'today'
                            ? $this->clearPreset()
                            : $this->applyToday()
                    ),

                Action::make('this_month')
                    ->label('This Month')
                    ->icon('heroicon-o-calendar')
                    ->color(fn () => $this->activePreset === 'this_month' ? 'primary' : 'gray')
                    ->action(fn () =>
                        $this->activePreset === 'this_month'
                            ? $this->clearPreset()
                            : $this->applyThisMonth()
                    ),

                Action::make('last_month')
                    ->label('Last Month')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color(fn () => $this->activePreset === 'last_month' ? 'primary' : 'gray')
                    ->action(fn () =>
                        $this->activePreset === 'last_month'
                            ? $this->clearPreset()
                            : $this->applyLastMonth()
                    ),

                Action::make('last_30_days')
                    ->label('Last 30 Days')
                    ->icon('heroicon-o-clock')
                    ->color(fn () => $this->activePreset === 'last_30_days' ? 'primary' : 'gray')
                    ->action(fn () =>
                        $this->activePreset === 'last_30_days'
                            ? $this->clearPreset()
                            : $this->applyLast30Days()
                    ),
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
