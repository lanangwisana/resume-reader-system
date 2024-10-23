<!DOCTYPE html>
<html>
<head>
    <title>Extracted Text</title>
</head>
<body>    
    <h2>Extracted Text from PDF</h2>
    @if (!empty($errors))
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <pre>{{ $text }}</pre>
</body>
</html>
