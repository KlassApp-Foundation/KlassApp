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
use Filament\Tables\Columns\IconColumn;
use App\Models\Country;

class Countries extends Component implements HasForms, HasTable
{	
	use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {	
    	$query = Country::orderBy('id','ASC'); //Country::where('status', 1)

       return $table
            ->query($query)
            ->headerActions([
	            CreateAction::make(),
	       	])
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('tel_prefix'),
                //TextColumn::make('status'),

                IconColumn::make('status')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')

                /*IconColumn::make('status'),IconColumn::make('status')
				    ->color(fn (string $state): string => match ($state) {
				        '1' => 'heroicon-o-check-circle',
				        '0' => 'heroicon-o-x-circle',
				    })*/
            ])
            ->filters([
                // ...
            ])
           ->actions([
                Action::make('Edit')
                ->url(fn (Country $r): string => route('superadmin.setting.countries.update', ['id' => $r])),
            ])
            ->bulkActions([
                // ...
            ])->paginated([2, 4, 50, 100, 'all']);


            //dd($m);
           
    }

    public function render()
    {
        return view('livewire.superadmin.setting.countries');
    }
}
