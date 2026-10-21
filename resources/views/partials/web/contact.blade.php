<div class="contact-layout">
    <x-web.contact-form
        :action="$action"
        :validation-url="$validationUrl"
        :fields="$fields"
        :title="$title"
        :subtitle="$subtitle"
        :desc="$description"
    />

    <x-web.contact-info />
</div>
