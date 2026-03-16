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

class Subscriptions extends Component implements HasForms, HasTable
{	
	use InteractsWithTable;
    use InteractsWithForms;

	public function table(Table $table): Table
    {	
    	$query = Subscription::query();

        return $table
            ->query($query)
            /*->headerActions([
	            CreateAction::make(),
	       	])*/
            ->columns([
            	TextColumn::make('school.name')->searchable()->sortable(),
            	TextColumn::make('status'),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                Action::make('edit')
                ->url(fn (Subscription $r): string => route('superadmin.reports.subscription.update', ['id' => $r])),
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
