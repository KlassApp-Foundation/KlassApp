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
use Filament\Tables\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use App\Models\State;

class States extends Component implements HasForms, HasTable
{	
	use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {	
    	$query = State::query(); //State::where('status', 1)

        return $table
            ->query($query)
            ->headerActions([
	            CreateAction::make(),
	       	])
            ->columns([
            	TextColumn::make('country.name'),
                TextColumn::make('name')->searchable()->sortable(),
                //TextColumn::make('status'),
                IconColumn::make('status')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
            ])
            ->filters([
                // ...
            ])
            
            ->actions([
                 Action::make('Edit')
                ->url(fn (State $r): string => route('superadmin.setting.states.update', ['id' => $r])),
            ])
            ->bulkActions([
                // ...
            ])->paginated([10, 25, 50, 100, 'all']);
    }

    public function render()
    {
        return view('livewire.superadmin.setting.states');
    }
}
