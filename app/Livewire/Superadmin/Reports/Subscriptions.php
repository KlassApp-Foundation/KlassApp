<?php

namespace App\Livewire\Superadmin\Reports;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use App\Models\Subscription;
use Carbon\Carbon;

class Subscriptions extends Component implements HasForms, HasTable
{	
	use InteractsWithTable;
    use InteractsWithForms;

	public function table(Table $table): Table
    {	
    	$query = Subscription::query()
         ->orderByRaw("
            CASE status
                WHEN 'pending' THEN 1
                WHEN 'approved' THEN 2
                WHEN 'canceled' THEN 3
                WHEN 'expired' THEN 4 
                ELSE 5
            END
        ")
        ->latest();
        return $table
            ->query($query)
            /*->headerActions([
	            CreateAction::make(),
	       	])*/
            // ->columns([
            // 	TextColumn::make('school.name')->searchable()->sortable(),
            // 	TextColumn::make('status'),
            // ])

            ->columns([
                   TextColumn::make('school.name')
                       ->label('School')
                       ->searchable()
                       ->sortable(),
               
                   TextColumn::make('status')
                       ->badge(),
               
                   TextColumn::make('start_date')
                       ->label('Start Date')
                       ->date()
                       ->sortable(),
               
                   TextColumn::make('end_date')
                       ->label('End Date')
                       ->date()
                       ->sortable(),
               
                   TextColumn::make('amount_paid')
                       ->money('UGX')
                       ->sortable(),
               
                   TextColumn::make('created_at')
                       ->label('Created')
                       ->dateTime()
                       ->sortable(),

                   TextColumn::make("payment_reference")
                        ->label("Reference")
                        ->sortable(),
                        
                   TextColumn::make("payment_method")
                        ->label("Method")
                        ->sortable()        
          ])

            ->filters([
                // ...
            ])
            // ->actions([
            //     Action::make('edit')
            //     ->url(fn (Subscription $r): string => route('superadmin.reports.subscription.update', ['id' => $r])),
            // ])
            ->actions([
                
                Action::make('approve')
                // ->requiresConfirmation()
                ->visible(fn (Subscription $record) => $record->status === 'pending')
                ->action(function (Subscription $record) {
                $startDate = Carbon::today();

                $record->update([
                    'status' => 'approved',
                    'start_date' => $startDate,
                    'end_date' => $startDate->copy()->addMonth(),
                ]);
                    session()->flash('message', 'Subscription approved successfully.');
                })
                ->color('success')
                ->extraAttributes([
                     'style' => 'background-color:#16a34a;color:white;padding:4px;border-radious:4px;',
                 ])
                ->icon('heroicon-o-check'),
            ])
            ->bulkActions([
                // ...
            ])->paginated([10, 25, 50, 100, 'all']);
           
    }

    public function render()
    {
        return view('livewire.superadmin.reports.subscriptions');
    }
}
