<cdash version="{{ $version }}">
    <status>{{ $status }}</status>
    <message>{{ $message }}</message>
    @if (isset($md5))
        <md5>{{ $md5 }}</md5>
    @endif
</cdash>
