<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Models\Feature;
use App\Models\Language;
use App\Models\MailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Validator;

class LanguageController extends Controller
{
    public function index()
    {
        $languages = Language::orderBy('sort_id', 'asc')->get();
        $idsArray = implode(',', $languages->pluck('id')->toArray());
        return view('backend.settings.languages.index', [
            'idsArray' => $idsArray,
            'languages' => $languages,
        ]);
    }

    public function sort(Request $request)
    {
        if ($request->has('ids') && !is_null($request->ids)) {
            $arr = explode(',', $request->ids);
            foreach ($arr as $sortOrder => $id) {
                $menu = Language::find($id);
                $menu->sort_id = $sortOrder;
                $menu->save();
            }
        }
        toastr()->success(admin_lang('updated Successfully'));
        return back();
    }

    public function create()
    {
        return view('backend.settings.languages.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150'],
            'flag' => ['required', 'image', 'mimes:png,jpg,jpeg'],
            'code' => ['required', 'string', 'max:10', 'min:2', 'unique:languages'],
            'direction' => ['required', 'integer', 'max:1'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back()->withInput();
        }
        if (!array_key_exists($request->code, languages())) {
            toastr()->error(admin_lang('Language code not supported'));
            return back();
        }
        $createNewLanguageFiles = $this->createNewLanguageFiles($request->code);
        if ($createNewLanguageFiles == "success") {
            $flag = imageUpload($request->file('flag'), 'images/flags/', null, $request->code);
            if ($flag) {
                $stortId = Language::get()->count() + 1;
                $language = Language::create([
                    'name' => $request->name,
                    'flag' => $flag,
                    'code' => $request->code,
                    'direction' => $request->direction,
                    'sort_id' => $stortId,
                ]);
                if ($language) {
                    $mailTemplates = MailTemplate::where('lang', config('app.default_language'))->get();
                    foreach ($mailTemplates as $mailTemplate) {
                        $newMailTemplate = new MailTemplate();
                        $newMailTemplate->lang = $language->code;
                        $newMailTemplate->alias = $mailTemplate->alias;
                        $newMailTemplate->name = $mailTemplate->name;
                        $newMailTemplate->subject = $mailTemplate->subject;
                        $newMailTemplate->body = $mailTemplate->body;
                        $newMailTemplate->shortcodes = $mailTemplate->shortcodes;
                        $newMailTemplate->status = $mailTemplate->status;
                        $newMailTemplate->save();
                    }
                    if ($request->has('is_default')) {
                        setEnv('DEFAULT_LANGUAGE', removeSpaces($language->code));
                    }
                    toastr()->success(admin_lang('Created Successfully'));
                    return redirect()->route('admin.settings.languages.translates', $language->code);
                }
            }
        } else {
            toastr()->error($createNewLanguageFiles);
            return back();
        }
    }

    public function translate(Request $request, $code, $group = null)
    {
        $language = Language::where('code', $code)->firstOrFail();
        $groups = array_map(function ($file) {
            return pathinfo($file)['filename'];
        }, File::files(base_path('lang/' . $language->code)));
        $active = $group ?? 'general';
        $translates = trans($active, [], $language->code);
        usort($groups, function ($a, $b) {
            if (strpos($a, 'general') !== false && strpos($b, 'general') === false) {
                return -1;
            } else if (strpos($a, 'general') === false && strpos($b, 'general') !== false) {
                return 1;
            } else {
                return 0;
            }
        });
        $defaultLanguage = trans($active, [], config('app.default_language'));
        return view('backend.settings.languages.translate', [
            'active' => $active,
            'groups' => $groups,
            'translates' => $translates,
            'language' => $language,
            'defaultLanguage' => $defaultLanguage,
        ]);
    }

    public function translateUpdate(Request $request, $id)
    {
        $language = Language::where('id', $id)->firstOrFail();
        // The group names a file inside lang/<code>/ — never a path.
        if (!is_string($request->group) || !preg_match('/^[A-Za-z0-9_-]+$/', $request->group) || !is_array($request->translates)) {
            toastr()->error(admin_lang('Language group file not exists'));
            return back();
        }
        $languageGroupFile = base_path('lang/' . $language->code . '/' . $request->group . '.php');
        if (!file_exists($languageGroupFile)) {
            toastr()->error(admin_lang('Language group file not exists'));
            return back();
        }
        $translations = include $languageGroupFile;
        foreach ($request->translates as $key1 => $value1) {
            if (is_array($value1)) {
                foreach ($value1 as $key2 => $value2) {
                    if (!is_array($translations[$key1] ?? null) || !array_key_exists($key2, $translations[$key1])) {
                        toastr()->error(admin_lang('Translations error'));
                        return back();
                    }
                }
            } else {
                if (!array_key_exists($key1, $translations)) {
                    toastr()->error(admin_lang('Translations error ' . $key1));
                    return back();
                }
            }
        }
        $fileContent = "<?php \n return " . var_export($request->translates, true) . ";";
        File::put($languageGroupFile, $fileContent, true);
        toastr()->success(admin_lang('Updated Successfully'));
        return back();
    }

    public function edit(Language $language)
    {
        return view('backend.settings.languages.edit', ['language' => $language]);
    }

    public function update(Request $request, Language $language)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:150'],
            'flag' => ['nullable', 'image', 'mimes:png,jpg,jpeg'],
            'direction' => ['required', 'integer', 'max:1'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back()->withInput();
        }
        if (!$request->has('is_default')) {
            if ($language->code == config('app.default_language')) {
                toastr()->error($language->name . ' ' . admin_lang('is default language'));
                return back();
            }
        }
        if ($request->has('flag')) {
            $flag = imageUpload($request->file('flag'), 'images/flags/', null, $language->code, $language->flag);
        } else {
            $flag = $language->flag;
        }
        if ($flag) {
            $updateLanguage = $language->update([
                'name' => $request->name,
                'flag' => $flag,
                'direction' => $request->direction,
            ]);
            if ($updateLanguage) {
                if ($request->has('is_default')) {
                    setEnv('DEFAULT_LANGUAGE', removeSpaces($language->code));
                }
                toastr()->success(admin_lang('Updated Successfully'));
                return back();
            }
        }
    }

    public function destroy(Language $language)
    {
        if ($language->code == config('app.default_language')) {
            toastr()->error(admin_lang('Default language cannot be deleted'));
            return back();
        }
        $articles = BlogArticle::where('lang', $language->code)->get();
        if ($articles->count() > 0) {
            foreach ($articles as $article) {
                removeFile($article->image);
            }
        }
        $features = Feature::where('lang', $language->code)->get();
        if ($features->count() > 0) {
            foreach ($features as $feature) {
                removeFile($feature->image);
            }
        }
        $deleteLanguageFiles = File::deleteDirectory(base_path('lang/' . $language->code));
        if ($deleteLanguageFiles) {
            $language->delete();
            toastr()->success(admin_lang('Deleted Successfully'));
            return back();
        }
    }

    public function export(Request $request, $code)
    {
        $language = Language::where('code', $code)->firstOrFail();
        if (!class_exists('ZipArchive')) {
            toastr()->error(admin_lang('ZipArchive extension is not enabled'));
            return back();
        }
        $languagePath = base_path('lang/' . $language->code);
        if (!is_dir($languagePath)) {
            toastr()->error(admin_lang('Language files not exists'));
            return back();
        }
        $zip = new \ZipArchive;
        $zipFile = $language->code . '_language.zip';
        if ($zip->open($zipFile, \ZipArchive::CREATE) === true) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($languagePath), \RecursiveIteratorIterator::LEAVES_ONLY);
            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($languagePath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
            return response()->download($zipFile)->deleteFileAfterSend(true);
        }
    }

    public function import(Request $request, $code)
    {
        $language = Language::where('code', $code)->firstOrFail();
        if (!class_exists('ZipArchive')) {
            toastr()->error(admin_lang('ZipArchive extension is not enabled'));
            return back();
        }
        $file = $request->file('language_file');
        if ($file->getClientOriginalExtension() != "zip") {
            toastr()->error(admin_lang('File type not allowed'));
            return back();
        }
        $zip = new \ZipArchive;
        $res = $zip->open($file->getRealPath());
        if ($res === true) {
            // Language files are PHP and get executed, so only accept flat
            // "<name>.php" entries that contain nothing but a returned array of strings.
            $files = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (str_ends_with($entry, '/')) {
                    continue;
                }
                $contents = $zip->getFromIndex($i);
                if (!preg_match('/^[A-Za-z0-9_-]+\.php$/', $entry) || $contents === false || !self::isPlainLanguageArray($contents)) {
                    $zip->close();
                    toastr()->error(admin_lang('Invalid language files'));
                    return back();
                }
                $files[$entry] = $contents;
            }
            $zip->close();
            if (empty($files)) {
                toastr()->error(admin_lang('Invalid language files'));
                return back();
            }
            $langPath = base_path('lang/' . $language->code);
            removeDirectory($langPath);
            makeDirectory($langPath);
            foreach ($files as $name => $contents) {
                File::put($langPath . '/' . $name, $contents);
            }
            toastr()->success(admin_lang('Language imported successfully'));
            return back();
        } else {
            toastr()->error(admin_lang('Failed to import language'));
            return back();
        }
    }

    /**
     * True when the PHP source only returns a (nested) array of literal strings
     * and numbers — no variables, function calls or any other code.
     */
    public static function isPlainLanguageArray(string $source): bool
    {
        $allowed = [T_OPEN_TAG, T_RETURN, T_ARRAY, T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DNUMBER, T_DOUBLE_ARROW, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];
        try {
            $tokens = token_get_all($source, TOKEN_PARSE);
        } catch (\ParseError $e) {
            return false;
        }
        if (empty($tokens) || !is_array($tokens[0]) || $tokens[0][0] !== T_OPEN_TAG) {
            return false;
        }
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (!in_array($token[0], $allowed, true)) {
                    return false;
                }
            } elseif (!in_array($token, ['[', ']', '(', ')', ',', ';'], true)) {
                return false;
            }
        }
        return true;
    }

    protected function createNewLanguageFiles($newLanguageCode)
    {
        try {
            $defaultLanguage = config('app.default_language');
            $langPath = base_path('lang/');
            if (!File::exists($langPath . $newLanguageCode)) {
                File::makeDirectory($langPath . $newLanguageCode);
                $defaultLanguageFiles = File::allFiles($langPath . $defaultLanguage);
                foreach ($defaultLanguageFiles as $file) {
                    $newFile = $langPath . $newLanguageCode . '/' . $file->getFilename();
                    if (!File::exists($newFile)) {
                        File::copy($file, $newFile);
                    }
                }
            }
            return "success";
        } catch (\Exception$e) {
            return $e->getMessage();
        }
    }
}