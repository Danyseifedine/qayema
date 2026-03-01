<x-guest-layout>
    <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Need an account?</h1>
        <p class="text-gray-600 mb-6">Contact us to get your menu account. We'll create it for you and you can log in to manage your menu.</p>
        <div class="space-y-4">
            <a href="tel:+96103004699"
                class="inline-flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg bg-orange-500 text-white font-semibold hover:bg-orange-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                +961 03 004 699
            </a>
            <a href="mailto:dany.a.seifeddine@gmail.com"
                class="inline-flex items-center justify-center gap-2 w-full px-4 py-3 rounded-lg border-2 border-gray-300 text-gray-700 font-semibold hover:border-orange-500 hover:text-orange-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                dany.a.seifeddine@gmail.com
            </a>
        </div>
        <p class="mt-6 text-sm text-gray-500">Barja, Lebanon</p>
        <a href="{{ route('login') }}" class="inline-block mt-6 text-sm text-orange-600 font-medium hover:text-orange-700">
            Already have an account? Log in
        </a>
    </div>
</x-guest-layout>
