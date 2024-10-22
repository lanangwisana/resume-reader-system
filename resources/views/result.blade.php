<!DOCTYPE html>
<html>
<head>
    <title>Extracted Text</title>
</head>
<body>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
            </ul>
        </div>
        @endif
        
    <h2>Extracted Text from PDF</h2>
    <pre>{{ $text }}</pre>
</body>
</html>
