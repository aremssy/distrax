<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\Deal;
use App\Models\LegalMatter;
use App\Models\Payment;
use App\Models\PropertyListing;
use App\Models\Technician;
use App\Models\User;

class AdminGlobalSearch
{
    /**
     * Search sidebar pages, users, listings, technicians, blog posts and payments
     * for the admin's typeahead, limited to the sections the admin is permitted
     * to view. Terms shorter than 2 characters return no results.
     *
     * @return array<int, array{group: string, icon: string, label: string, sub: string, url: string}>
     */
    public function search(User $admin, string $term): array
    {
        if (mb_strlen($term) < 2) {
            return [];
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
        $results = $this->matchingPages($admin, $term);

        if ($this->canView($admin, 'users.view')) {
            $users = User::where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like))
                ->limit(5)
                ->get(['id', 'name', 'email']);

            foreach ($users as $user) {
                $results[] = [
                    'group' => 'Users',
                    'icon' => 'users',
                    'label' => $user->name,
                    'sub' => $user->email,
                    'url' => route('admin.users.show', $user),
                ];
            }
        }

        if ($this->canView($admin, 'listings.view')) {
            $listings = PropertyListing::where(fn ($query) => $query->where('title', 'like', $like)->orWhere('address', 'like', $like))
                ->limit(5)
                ->get(['id', 'title', 'status']);

            foreach ($listings as $listing) {
                $results[] = [
                    'group' => 'Listings',
                    'icon' => 'building-office-2',
                    'label' => $listing->title,
                    'sub' => ucfirst($listing->status),
                    'url' => route('admin.listings.show', $listing),
                ];
            }
        }

        if ($this->canView($admin, 'technicians.view')) {
            $technicians = Technician::with('user:id,name')
                ->whereHas('user', fn ($query) => $query->where('name', 'like', $like))
                ->limit(5)
                ->get(['id', 'user_id', 'status']);

            foreach ($technicians as $technician) {
                $results[] = [
                    'group' => 'Technicians',
                    'icon' => 'wrench-screwdriver',
                    'label' => $technician->user->name ?? 'Unknown',
                    'sub' => ucfirst($technician->status),
                    'url' => route('admin.technicians.show', $technician),
                ];
            }
        }

        if ($this->canView($admin, 'blogs.view')) {
            $blogs = Blog::where('title', 'like', $like)
                ->limit(5)
                ->get(['id', 'title', 'status']);

            foreach ($blogs as $blog) {
                $results[] = [
                    'group' => 'Blog Posts',
                    'icon' => 'newspaper',
                    'label' => $blog->title,
                    'sub' => ucfirst($blog->status),
                    'url' => route('admin.blogs.show', $blog),
                ];
            }
        }

        if ($this->canView($admin, 'payments.view')) {
            $payments = Payment::with('user:id,name')
                ->where('transaction_ref', 'like', $like)
                ->limit(5)
                ->get(['id', 'user_id', 'transaction_ref', 'amount', 'currency']);

            foreach ($payments as $payment) {
                $results[] = [
                    'group' => 'Payments',
                    'icon' => 'banknotes',
                    'label' => $payment->transaction_ref,
                    'sub' => money($payment->amount, $payment->currency).' — '.($payment->user->name ?? 'Unknown'),
                    'url' => route('admin.payments.show', $payment),
                ];
            }
        }

        if ($this->canView($admin, 'deals.view')) {
            $deals = Deal::with(['listing:id,title', 'buyer:id,name'])
                ->whereHas('listing', fn ($q) => $q->where('title', 'like', $like))
                ->orWhereHas('buyer', fn ($q) => $q->where('name', 'like', $like))
                ->orWhereHas('seller', fn ($q) => $q->where('name', 'like', $like))
                ->limit(5)
                ->get(['id', 'property_listing_id', 'buyer_id', 'stage']);

            foreach ($deals as $deal) {
                $results[] = [
                    'group' => 'Deals',
                    'icon' => 'hand-coins',
                    'label' => $deal->listing?->title ?? 'Deal #'.$deal->id,
                    'sub' => ucfirst(str_replace('_', ' ', $deal->stage)),
                    'url' => route('admin.deals.show', $deal),
                ];
            }
        }

        if ($this->canView($admin, 'legal_matters.view')) {
            $matters = LegalMatter::with(['deal.listing:id,title'])
                ->whereHas('deal.listing', fn ($q) => $q->where('title', 'like', $like))
                ->orWhere('type', 'like', $like)
                ->limit(5)
                ->get(['id', 'deal_id', 'type', 'status']);

            foreach ($matters as $matter) {
                $results[] = [
                    'group' => 'Legal Matters',
                    'icon' => 'scale',
                    'label' => ucfirst(str_replace('_', ' ', $matter->type)),
                    'sub' => ($matter->deal?->listing?->title ?? 'Deal #'.$matter->deal_id).' · '.ucfirst($matter->status),
                    'url' => route('admin.legal-matters.show', $matter),
                ];
            }
        }

        return $results;
    }

    /**
     * Sidebar pages whose label matches the term, limited to pages the admin
     * may see. Listed first so "Coupons" jumps to the page before records.
     *
     * @return array<int, array{group: string, icon: string, label: string, sub: string, url: string}>
     */
    private function matchingPages(User $admin, string $term): array
    {
        $results = [];
        $section = 'General';

        foreach (AdminMenu::items() as $item) {
            if (isset($item['divider'])) {
                $section = $item['label'];

                continue;
            }

            if (mb_stripos($item['label'], $term) === false) {
                continue;
            }

            if ($item['permission'] !== 'dashboard.view' && ! $this->canView($admin, $item['permission'])) {
                continue;
            }

            $results[] = [
                'group' => 'Pages',
                'icon' => $item['icon'],
                'label' => $item['label'],
                'sub' => $section,
                'url' => route($item['route']),
            ];
        }

        return array_slice($results, 0, 6);
    }

    private function canView(User $admin, string $permission): bool
    {
        return $admin->isSuperAdmin() || $admin->hasPermission($permission);
    }
}
