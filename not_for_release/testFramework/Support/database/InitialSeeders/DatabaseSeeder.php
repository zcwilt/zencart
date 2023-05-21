<?php

namespace InitialSeeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(ConfigurationTableSeeder::class);
        $this->call(AddressFormatTableSeeder::class);
        $this->call(AdminTableSeeder::class);
        $this->call(AdminMenusTableSeeder::class);
        $this->call(AdminPagesTableSeeder::class);
        $this->call(AdminPagesToProfilesTableSeeder::class);
        $this->call(AdminProfilesTableSeeder::class);
        $this->call(BannersTableSeeder::class);
        $this->call(ConfigurationGroupTableSeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(CurrenciesTableSeeder::class);
        $this->call(GeoZonesTableSeeder::class);
        $this->call(GetTermsToFilterTableSeeder::class);
        $this->call(LanguagesTableSeeder::class);
        $this->call(LayoutBoxesTableSeeder::class);
        $this->call(MediaTypesTableSeeder::class);
        $this->call(OrdersStatusTableSeeder::class);
        $this->call(PaypalPaymentStatusTableSeeder::class);
        $this->call(ProductTypeLayoutTableSeeder::class);
        $this->call(ProductTypesTableSeeder::class);
        $this->call(ProductsOptionsTypesTableSeeder::class);
        $this->call(ProductsOptionsValuesTableSeeder::class);
        $this->call(ProjectVersionTableSeeder::class);
        $this->call(ProjectVersionHistoryTableSeeder::class);
        $this->call(QueryBuilderTableSeeder::class);
        $this->call(TaxClassTableSeeder::class);
        $this->call(TaxRatesTableSeeder::class);
        $this->call(TemplateSelectTableSeeder::class);
        $this->call(ZonesTableSeeder::class);
        $this->call(ZonesToGeoZonesTableSeeder::class);
    }
}
