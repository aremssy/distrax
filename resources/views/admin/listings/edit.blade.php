<x-admin-layout :title="'Edit: '.$listing->title">
    <x-admin-page-header :title="'Edit: '.$listing->title"
        :breadcrumbs="[['label' => 'Listings', 'route' => 'admin.listings.index'], ['label' => '#'.$listing->id], ['label' => 'Edit']]"
        description="Update listing details, media, and custom field values." />

    @include('admin.listings._form', [
        'action' => route('admin.listings.update', $listing),
        'method' => 'PUT',
        'submitLabel' => 'Save Changes',
    ])
</x-admin-layout>
