<div id="schoolList" class="grid md:grid-cols-2 gap-6">
    <?php if (!$schools): ?>
        <p class="text-center text-gray-500 col-span-2">কোনো স্কুল পাওয়া যায়নি।</p>
    <?php else: ?>
        <?php foreach ($schools as $s): ?>
            <?php
            $sid = $s['id'];
            $notes = $notesBySchool[$sid] ?? [];
            $noteCount = count($notes);
            // notes query আমরা note_date DESC করেছি, তাই index 0 = last (latest) note
            $latestNote = $noteCount ? $notes[0] : null;
            ?>
            <div class="bg-white p-4 shadow rounded-xl relative">
                <?php if (!empty($s['photo_path'])): ?>
                    <img src="<?php echo htmlspecialchars($s['photo_path']); ?>" class="w-full h-48 object-cover rounded mb-3">
                <?php endif; ?>
                <h3 class="text-xl font-bold"><?php echo htmlspecialchars($s['school_name']); ?></h3>
                <p class="text-gray-600"><?php echo htmlspecialchars($s['mobile']); ?></p>
                <p class="text-sm text-indigo-600 font-bold">
                    <?php echo htmlspecialchars($s['district'] . ', ' . $s['upazila']); ?>
                </p>
                <p class="mt-2 font-semibold">
                    Status:
                    <span class="<?php echo $s['status'] === 'Approved' ? 'text-green-600' : 'text-orange-600'; ?>">
                        <?php echo htmlspecialchars($s['status']); ?>
                    </span>
                </p>

                <!-- Notes title + View all button -->
                <div class="mt-3 flex items-center justify-between">
                    <span class="font-bold">Notes:</span>
                    <!-- View all button -->
                    <?php if ($noteCount > 1): ?>
                        <button type="button" class="text-sm text-blue-600 hover:underline" onclick="openNotesModal(this)"
                            data-id="<?php echo $sid; ?>"
                            data-name="<?php echo htmlspecialchars($s['school_name'], ENT_QUOTES); ?>">
                            View all (<?php echo $noteCount; ?>)
                        </button>
                    <?php endif; ?>

                </div>

                <!-- শুধু last (latest) note দেখাবে -->
                <?php if ($latestNote): ?>
                    <div class="bg-gray-100 p-2 rounded mt-2">
                        <p><?php echo nl2br(htmlspecialchars($latestNote['note_text'])); ?></p>
                        <small class="text-gray-500">
                            <?php echo htmlspecialchars($latestNote['note_date']); ?>
                        </small>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-400 mt-1">No notes yet.</p>
                <?php endif; ?>




                <div class="flex gap-2 mt-3">
                    <button type="button" onclick="openEditModal(this)"
                        class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700"
                        data-id="<?php echo $sid; ?>" data-name="<?php echo htmlspecialchars($s['school_name'], ENT_QUOTES); ?>"
                        data-mobile="<?php echo htmlspecialchars($s['mobile'], ENT_QUOTES); ?>"
                        data-district="<?php echo htmlspecialchars($s['district'], ENT_QUOTES); ?>"
                        data-upazila="<?php echo htmlspecialchars($s['upazila'], ENT_QUOTES); ?>"
                        data-status="<?php echo htmlspecialchars($s['status'], ENT_QUOTES); ?>">
                        Edit
                    </button>
                    <button type="button" onclick="openAddNoteModal(<?php echo $sid; ?>)"
                        class="flex-1 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Add Note
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>