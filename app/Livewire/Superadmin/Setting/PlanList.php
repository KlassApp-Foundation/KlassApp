<?php

namespace App\Livewire\Superadmin\Setting;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput;
use App\Models\Plan;

class PlanList extends Component implements HasForms, HasTable
{	
	use InteractsWithTable;
    use InteractsWithForms;

	public function table(Table $table): Table
    {	
    	$query = Plan::query();

        return $table
            ->query($query)
            ->headerActions([
	            CreateAction::make(),
	       	])
            ->columns([
            	TextColumn::make('cycle'),
            	TextColumn::make('name')->searchable()->sortable(),
            	TextColumn::make('order'),
            	TextColumn::make('is_active'),
            	TextColumn::make('amount'),                
            ])
            ->filters([
                // ...
            ])
            ->actions([
                Action::make('Edit')
                ->url(fn (Plan $r): string => route('superadmin.setting.plan.update', ['id' => $r])),
            ])
            ->bulkActions([
                // ...
            ])->paginated([10, 25, 50, 100, 'all']);
           
    }

    public function render()
    {
        return view('livewire.superadmin.setting.plan-list');
    }
}
