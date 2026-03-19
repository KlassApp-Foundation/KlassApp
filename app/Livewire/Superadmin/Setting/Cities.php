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
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use App\Models\City;

class Cities extends Component implements HasForms, HasTable
{	
	use InteractsWithTable;
    use InteractsWithForms;

	public function table(Table $table): Table
    {	
    	$query = City::orderBy('id','ASC'); //where('status', 1);
    //	dd($query);

        return $table
            ->query($query)
            ->columns([
            	TextColumn::make('state.name')->label('State')->searchable(),
                TextColumn::make('name')->searchable()->sortable(),
                IconColumn::make('status')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                /*BadgeColumn::make('status')
                    ->colors([
                        'primary',
                        'success' => static fn ($state): bool => $record->status === '1',
                        'danger' => static fn ($state): bool => $record->status === '0',
                    ])*/

                /*IconColumn::make('status')
                    ->icon(fn (string $state): string => match ($state) {
                        '0' => 'heroicon-o-clock',
                        '1' => 'heroicon-o-check-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                    })*/
            ])
            ->filters([
                // ...
            ])
            ->actions([
                Action::make('Edit')
                ->url(fn (City $r): string => route('superadmin.setting.cities.update', ['id' => $r])),
            ])
            ->bulkActions([
                // ...
            ])->paginated([10, 25, 50, 100, 'all']);
           
    }

    public function render()
    {
        return view('livewire.superadmin.setting.cities');
    }
}
