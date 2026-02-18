

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quiz</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Edit Quiz</h1>
        
        <form method="POST" action="/admin/quiz/update">
            <div class="form-group">
                <label for="title">Quiz Title</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"></textarea>
            </div>

            <div class="form-group">
                <label for="duration">Duration (minutes)</label>
                <input type="number" id="duration" name="duration" min="1" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update Quiz</button>
            <a href="/admin/quiz" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>