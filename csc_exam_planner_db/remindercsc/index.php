<?php

$host = 'sqlXXX.epizy.com'; // your MySQL host from InfinityFree
$db   = 'if0_40397968_csc_exam_planner';
$user = 'if0_40397968';     // your MySQL username
$pass = 'YOUR_PASSWORD';    // your MySQL password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Tabs info with enhanced styling
$tabs = [
    'mockExams' => ['label' => 'Mock Exams', 'icon' => '📝', 'color' => 'purple', 'gradient' => 'from-purple-400 to-purple-600'],
    'driveLinks' => ['label' => 'Drive Links', 'icon' => '🔗', 'color' => 'blue', 'gradient' => 'from-blue-400 to-blue-600'],
    'quotes' => ['label' => 'Motivation', 'icon' => '💡', 'color' => 'amber', 'gradient' => 'from-amber-400 to-amber-600'],
    'tips' => ['label' => 'Study Tips', 'icon' => '📚', 'color' => 'green', 'gradient' => 'from-green-400 to-green-600']
];

$activeTab = $_GET['tab'] ?? 'mockExams';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $category = $_POST['category'] ?? '';
    $id = $_POST['id'] ?? '';

    // ADD ITEM
    if ($action === 'add' && isset($_POST['title'], $_POST['content'])) {
        $title = htmlspecialchars($_POST['title']);
        $content = htmlspecialchars($_POST['content']);

        // Handle image upload
        $imgPath = null;
        if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/assets/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileName = uniqid() . '_' . basename($_FILES['img']['name']);
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['img']['tmp_name'], $targetFile)) {
                $imgPath = 'assets/' . $fileName;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO planner_items (category, title, content, img_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$category, $title, $content, $imgPath]);
    }

    // DELETE ITEM
    if ($action === 'delete' && $id !== '') {
        $stmt = $pdo->prepare("SELECT img_path FROM planner_items WHERE item_id=?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($item && !empty($item['img_path']) && file_exists(__DIR__ . '/' . $item['img_path'])) {
            unlink(__DIR__ . '/' . $item['img_path']);
        }
        $stmt = $pdo->prepare("DELETE FROM planner_items WHERE item_id=?");
        $stmt->execute([$id]);
    }

    header('Location: ' . $_SERVER['PHP_SELF'] . '?tab=' . $activeTab);
    exit;
}

// Fetch items
$stmt = $pdo->prepare("SELECT * FROM planner_items WHERE category=? ORDER BY created_at DESC");
$stmt->execute([$activeTab]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CSC Exam Planner </title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
@keyframes fadeInUp {
    from {opacity:0; transform:translateY(30px);}
    to {opacity:1; transform:translateY(0);}
}
@keyframes slideIn {
    from {opacity:0; transform:translateX(-20px);}
    to {opacity:1; transform:translateX(0);}
}
@keyframes float {
    0%, 100% {transform:translateY(0px);}
    50% {transform:translateY(-10px);}
}
@keyframes pulse-slow {
    0%, 100% {opacity:1;}
    50% {opacity:0.7;}
}
@keyframes shimmer {
    0% {background-position: -200% 0;}
    100% {background-position: 200% 0;}
}

.fade-in-up {animation:fadeInUp 0.6s ease-out;}
.slide-in {animation:slideIn 0.5s ease-out;}
.float-animation {animation:float 3s ease-in-out infinite;}
.pulse-slow {animation:pulse-slow 2s ease-in-out infinite;}

.card-3d {
    transform-style: preserve-3d;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.card-3d:hover {
    transform: translateY(-8px) rotateX(2deg);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.shimmer-bg {
    background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
    background-size: 200% 100%;
    animation: shimmer 2s infinite;
}

.glass-effect {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.image-container {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
}

.image-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.1) 100%);
    opacity: 0;
    transition: opacity 0.3s;
}

.image-container:hover .image-overlay {
    opacity: 1;
}

.sticker-badge {
    transform: rotate(-15deg);
    transition: all 0.3s ease;
}

.sticker-badge:hover {
    transform: rotate(0deg) scale(1.1);
}

.tab-indicator {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: currentColor;
    transition: all 0.3s ease;
}

/* Custom scrollbar */
::-webkit-scrollbar {width: 10px;}
::-webkit-scrollbar-track {background: #f1f1f1;}
::-webkit-scrollbar-thumb {background: #a855f7; border-radius: 5px;}
::-webkit-scrollbar-thumb:hover {background: #9333ea;}
</style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 min-h-screen font-sans">

<!-- Decorative Background Elements -->
<div class="fixed inset-0 overflow-hidden pointer-events-none">
    <div class="absolute top-20 left-10 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 float-animation"></div>
    <div class="absolute bottom-20 right-10 w-96 h-96 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 float-animation" style="animation-delay: 1s;"></div>
    <div class="absolute top-1/2 left-1/2 w-80 h-80 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 float-animation" style="animation-delay: 2s;"></div>
</div>

<div class="container mx-auto px-4 py-8 max-w-6xl relative z-10">

    <!-- Enhanced Header -->
    <div class="text-center mb-10 fade-in-up">
        <div class="inline-block mb-4">
            <div class="text-7xl mb-3 float-animation">🤬</div>
        </div>
        <h1 class="text-6xl font-extrabold bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 bg-clip-text text-transparent mb-3 tracking-tight">
            CSC Exam Planner
        </h1>
        <p class="text-gray-600 text-xl font-medium">MAG REVIEW KA ✨</p>
        <div class="flex items-center justify-center gap-2 mt-4 text-sm text-gray-500">
            <span class="px-3 py-1 bg-white rounded-full shadow-sm">📅 Organized</span>
            <span class="px-3 py-1 bg-white rounded-full shadow-sm">💪 Motivated</span>
            <span class="px-3 py-1 bg-white rounded-full shadow-sm">🎯 Focused</span>
        </div>
    </div>

    <!-- Enhanced Tabs -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 slide-in">
        <?php foreach($tabs as $key => $tab): ?>
        <a href="?tab=<?= $key ?>" class="block group">
            <div class="relative overflow-hidden <?= $activeTab === $key ? 'glass-effect shadow-xl scale-105' : 'bg-white/70 shadow-md' ?> rounded-2xl p-5 text-center transition-all duration-300 hover:shadow-xl hover:scale-105">
                <?php if($activeTab === $key): ?>
                    <div class="absolute inset-0 bg-gradient-to-br <?= $tab['gradient'] ?> opacity-10"></div>
                <?php endif; ?>
                <div class="relative z-10">
                    <div class="text-4xl mb-3 transform group-hover:scale-110 transition-transform duration-300"><?= $tab['icon'] ?></div>
                    <div class="font-bold text-gray-800 text-lg mb-1"><?= $tab['label'] ?></div>
                    <div class="inline-flex items-center gap-1 text-sm font-semibold <?= $activeTab === $key ? 'text-'.$tab['color'].'-600' : 'text-gray-500' ?>">
                        <span class="w-2 h-2 rounded-full <?= $activeTab === $key ? 'bg-'.$tab['color'].'-500' : 'bg-gray-400' ?> pulse-slow"></span>
                        <?= count($items) ?> items
                    </div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Enhanced Add Button -->
    <div class="mb-8 text-center slide-in">
        <button onclick="openModal()" class="bg-gradient-to-r <?= $tabs[$activeTab]['gradient'] ?> hover:shadow-2xl text-white px-10 py-4 rounded-full font-bold shadow-lg inline-flex items-center gap-3 transform hover:scale-105 transition-all duration-300 group">
            <span class="text-2xl group-hover:rotate-90 transition-transform duration-300">+</span> 
            <span class="text-lg">Add New <?= $tabs[$activeTab]['label'] ?></span>
        </button>
    </div>

    <!-- Enhanced Items List -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if(empty($items)): ?>
            <div class="text-center py-16 col-span-full fade-in-up">
                <div class="glass-effect rounded-3xl p-12 inline-block">
                    <div class="text-8xl mb-6 opacity-50"><?= $tabs[$activeTab]['icon'] ?></div>
                    <p class="text-gray-600 text-xl font-medium mb-2">No items yet!</p>
                    <p class="text-gray-500">Start building your <?= strtolower($tabs[$activeTab]['label']) ?> collection</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach($items as $index => $item): ?>
            <div class="card-3d glass-effect rounded-2xl shadow-lg overflow-hidden fade-in-up" style="animation-delay: <?= $index * 0.1 ?>s;">
                
                <!-- Image Section (Enhanced) -->
                <?php if(!empty($item['img_path'])): ?>
                <div class="image-container h-48 bg-gradient-to-br from-gray-100 to-gray-200">
                    <img src="<?= $item['img_path'] ?>" alt="sticker" class="w-full h-full object-cover">
                    <div class="image-overlay"></div>
                </div>
                <?php else: ?>
                <div class="h-48 bg-gradient-to-br <?= $tabs[$activeTab]['gradient'] ?> flex items-center justify-center">
                    <div class="text-7xl opacity-30"><?= $tabs[$activeTab]['icon'] ?></div>
                </div>
                <?php endif; ?>

                <!-- Content Section -->
                <div class="p-6 relative">
                    <!-- Delete Button -->
                    <form method="POST" class="absolute top-3 right-3">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="category" value="<?= $activeTab ?>">
                        <input type="hidden" name="id" value="<?= $item['item_id'] ?>">
                        <button type="submit" onclick="return confirm('Are you sure you want to delete this item?')" 
                                class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-full shadow-md transform hover:scale-110 transition-all duration-300">
                            🗑️
                        </button>
                    </form>

                    <!-- Title -->
                    <h3 class="text-xl font-bold text-gray-800 mb-3 pr-12 leading-tight"><?= $item['title'] ?></h3>
                    
                    <!-- Content -->
                    <?php if($activeTab==='mockExams' || $activeTab==='driveLinks'): ?>
                        <a href="<?= $item['content'] ?>" target="_blank" 
                           class="inline-flex items-center gap-2 bg-gradient-to-r <?= $tabs[$activeTab]['gradient'] ?> text-white px-4 py-2 rounded-lg font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
                            Open Link 🔗
                        </a>
                    <?php else: ?>
                        <p class="text-gray-700 leading-relaxed mb-4 line-clamp-3"><?= $item['content'] ?></p>
                    <?php endif; ?>
                    
                    <!-- Footer -->
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <span>📅</span>
                            <span><?= date('M d, Y', strtotime($item['created_at'])) ?></span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br <?= $tabs[$activeTab]['gradient'] ?> opacity-20"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Modal -->
<div id="modal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" onclick="closeModalOnBackdrop(event)">
    <div class="glass-effect rounded-3xl max-w-lg w-full p-8 shadow-2xl transform scale-95 transition-all duration-300" id="modalContent">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-3xl font-bold bg-gradient-to-r <?= $tabs[$activeTab]['gradient'] ?> bg-clip-text text-transparent">
                    Add New Item
                </h2>
                <p class="text-gray-600 text-sm mt-1">Fill in the details below</p>
            </div>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-3xl transform hover:rotate-90 transition-all duration-300">×</button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="category" value="<?= $activeTab ?>">

            <div>
                <label class="block text-gray-700 font-bold mb-2 flex items-center gap-2">
                    <span>✏️</span> Title
                </label>
                <input type="text" name="title" required 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-<?= $tabs[$activeTab]['color'] ?>-400 focus:ring-2 focus:ring-<?= $tabs[$activeTab]['color'] ?>-200 outline-none transition-all duration-300"
                       placeholder="Enter a catchy title...">
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2 flex items-center gap-2">
                    <span>📝</span> Content or Link
                </label>
                <textarea name="content" required rows="4" 
                          class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-<?= $tabs[$activeTab]['color'] ?>-400 focus:ring-2 focus:ring-<?= $tabs[$activeTab]['color'] ?>-200 outline-none transition-all duration-300 resize-none"
                          placeholder="<?= ($activeTab==='mockExams' || $activeTab==='driveLinks') ? 'Paste your link here...' : 'Write something inspiring...' ?>"></textarea>
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-2 flex items-center gap-2">
                    <span>🖼️</span> Upload Image <span class="text-xs font-normal text-gray-500">(optional)</span>
                </label>
                <div class="relative">
                    <input type="file" name="img" accept="image/*" id="fileInput"
                           class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-xl focus:border-<?= $tabs[$activeTab]['color'] ?>-400 outline-none transition-all duration-300 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-gradient-to-r file:<?= $tabs[$activeTab]['gradient'] ?> file:text-white file:font-semibold file:cursor-pointer hover:file:shadow-lg">
                    <p class="text-xs text-gray-500 mt-2">Recommended: Square images work best!</p>
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-gradient-to-r <?= $tabs[$activeTab]['gradient'] ?> hover:shadow-xl text-white py-4 rounded-xl font-bold transform hover:scale-105 transition-all duration-300">
                    ✨ Add Item
                </button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 py-4 rounded-xl font-bold transition-all duration-300">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    const modal = document.getElementById('modal');
    const modalContent = document.getElementById('modalContent');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modalContent.style.transform = 'scale(1)';
    }, 10);
}

function closeModal() {
    const modal = document.getElementById('modal');
    const modalContent = document.getElementById('modalContent');
    modalContent.style.transform = 'scale(0.95)';
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function closeModalOnBackdrop(event) {
    if (event.target.id === 'modal') {
        closeModal();
    }
}

// Keyboard shortcut to close modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

</body>
</html>

