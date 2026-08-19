<x-admin-layout title="Create Listing">
    <x-admin-page-header title="Create Listing"
        :breadcrumbs="[['label' => 'Listings', 'route' => 'admin.listings.index'], ['label' => 'Create']]"
        description="Create a moderated property listing from the admin panel." />

    @include('admin.listings._form', [
        'action' => route('admin.listings.store'),
        'method' => 'POST',
        'submitLabel' => 'Create Listing',
    ])
</x-admin-layout>
