<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Workflow</h2>
            <a class="text-sm text-indigo-600 hover:underline" href="{{ route('dashboard') }}">Back</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('workflows.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input
                            class="mt-1 w-full border rounded px-3 py-2"
                            name="title"
                            value="{{ old('title') }}"
                            required
                            maxlength="255"
                        />
                        @error('title')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Body</label>
                        <textarea
                            class="mt-1 w-full border rounded px-3 py-2"
                            name="body"
                            rows="6"
                            maxlength="5000"
                        >{{ old('body') }}</textarea>
                        @error('body')
                            <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex gap-3">
                        <button class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700" type="submit">
                            Create
                        </button>
                        <a class="px-4 py-2 rounded border" href="{{ route('dashboard') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>