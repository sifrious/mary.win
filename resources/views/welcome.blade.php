<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

@include('partials.head')

<body class="bg-white text-black">
    <div
        class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
        <main class="flex w-4/5 flex-col-reverse lg:max-w-4xl lg:flex-row">
            <div>
                <h1 class="mb-1 font-medium text-2xl">OMT (Object Modeling Technique) Symbols</h1>
                <p class="py-2">The slides I am presenting all contain <em>Class Diagrams</em>. One pattern will
                    contain an
                    interaction diagram, but more information will be uploaded for that at a later time. Please checkout
                    <a href="">pinkary @sifrious</a>
                    and I'll let you know when this page uploads and gets neat new features. Like CSS.
                </p>
                <p class="py-2">This is very basic; a more complete reference is available in
                    <a href="https://en.wikipedia.org/wiki/Design_Patterns">Design Patterns: Objects of Reusable
                        Design</a>
                </p>
                <ul class="list-none p-0 flex flex-col w-2/3 justify-between">
                    <li class="flex items-center mb-2.5">
                        <img src="{{ asset('images/subclass.png') }}" alt="Subclass"
                            class="w-[150px] h-[150px] object-cover mr-4" />
                        Indicates a class inherits from another class.
                    </li>
                    <li class="flex items-center mb-2.5">
                        <img src="{{ asset('images/relation.png') }}" alt="Relation"
                            class="w-[150px] h-[150px] object-cover mr-4" />
                        Indicates a relationship between two classes.
                    </li>
                    <li class="flex items-center mb-2.5">
                        <img src="{{ asset('images/part_of.png') }}" alt="Part Of"
                            class="w-[150px] h-[150px] object-cover mr-4" />
                        Indicates a class is part of another class.
                    </li>
                    <li class="flex items-center mb-2.5">
                        <img src="{{ asset('images/more_than_one.png') }}" alt="More Than One"
                            class="w-[150px] h-[150px] object-cover mr-4" />
                        Indicates more than one exist.
                    </li>
                </ul>
            </div>
    </div>
    </main>

</body>

</html>
