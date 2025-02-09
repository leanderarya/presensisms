import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, useForm } from "@inertiajs/react";
import { toast } from "sonner";
import { AlertTriangle } from "lucide-react";

export default function EditKonfigurasiJamkerja({ konfigurasi_shift_kerja }) {
    const {
        data,
        setData,
        put,
        delete: destroy,
        errors,
        processing,
    } = useForm({
        id: konfigurasi_shift_kerja.id,
        kode_jamkerja: konfigurasi_shift_kerja.kode_jamkerja,
        nama_jamkerja: konfigurasi_shift_kerja.nama_jamkerja,
        awal_jam_masuk: konfigurasi_shift_kerja.awal_jam_masuk,
        jam_masuk: konfigurasi_shift_kerja.jam_masuk,
        jam_pulang: konfigurasi_shift_kerja.jam_pulang,
    });

    function handleSubmit(e) {
        e.preventDefault();
        put(
            route("konfigurasi.update", {
                konfigurasi: konfigurasi_shift_kerja.id,
            }),
            {
                onSuccess: () => {
                    toast.success("Data shift berhasil di perbarui");
                },
            }
        );
    }

    function handleDestroy(e) {
        e.preventDefault();

        // Tampilkan alert konfirmasi sebelum menghapus data
        toast(
            (t) => (
                <div className="flex flex-col items-center justify-center space-y-4 p-4 bg-red-100 border border-red-400 rounded-lg">
                    <div className="flex items-center gap-2">
                        <AlertTriangle className="text-red-600 w-20 h-20" />
                        <p className="text-lg font-semibold text-red-600">
                            Apakah Anda yakin ingin menghapus data ini?
                        </p>
                    </div>
                    <div className="flex gap-4">
                        <button
                            onClick={() => confirmDestroy(t)}
                            className="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700"
                        >
                            OK
                        </button>
                        <button
                            onClick={() => toast.dismiss(t)}
                            className="px-4 py-2 text-red-600 bg-red-200 rounded-lg hover:bg-red-300"
                        >
                            Batal
                        </button>
                    </div>
                </div>
            ),
            { duration: Infinity }
        );
    }

    function confirmDestroy(toastId) {
        // Hapus data setelah konfirmasi
        destroy(route("konfigurasi.destroy", konfigurasi_shift_kerja.id), {
            onSuccess: () => {
                toast.dismiss(toastId);
                toast.success("Data shift berhasil dihapus");
            },
            onError: () => {
                toast.dismiss(toastId);
                toast.error("Terjadi kesalahan saat menghapus data");
            },
        });
    }

    return (
        <>
            <AuthenticatedLayout
                header={
                    <>
                        <h1>Edit Konfigurasi Jam karyawan</h1>
                    </>
                }
                children={
                    <>
                        <Head title="Edit Konfigurasi" />
                        <div className="p-6 bg-white shadow-md rounded-lg">
                            <form
                                onSubmit={handleSubmit}
                                encType="multipart/form-data"
                            >
                                <div className="space-y-4">
                                    {/* Kode Jam Kerja */}
                                    <div className="flex items-center">
                                        <input
                                            className="block w-full rounded-md border-0 p-2 text-slate-900 shadow-sm ring-1 ring-slate-300 placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500 sm:text-sm bg-white"
                                            type="hidden"
                                            placeholder="Kode Jam Kerja SHFOP .."
                                            value={data.id}
                                            onChange={(e) =>
                                                setData(
                                                    "kode_jamkerja",
                                                    e.target.value
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="flex items-center">
                                        <label className="w-40 font-medium">
                                            Kode Jam Kerja:
                                        </label>
                                        <input
                                            className="block w-full rounded-md border-0 p-2 text-slate-900 shadow-sm ring-1 ring-slate-300 placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500 sm:text-sm bg-white"
                                            type="text"
                                            placeholder="Kode Jam Kerja SHFOP .."
                                            value={data.kode_jamkerja}
                                            onChange={(e) =>
                                                setData(
                                                    "kode_jamkerja",
                                                    e.target.value
                                                )
                                            }
                                        />
                                    </div>
                                    {errors.kode_jamkerja && (
                                        <p className="text-red-500 text-sm ml-40">
                                            {errors.kode_jamkerja}
                                        </p>
                                    )}

                                    {/* Nama Jam Kerja */}
                                    <div className="flex items-center">
                                        <label className="w-40 font-medium">
                                            Nama Jam Kerja:
                                        </label>
                                        <input
                                            className="block w-full rounded-md border-0 p-2 text-slate-900 shadow-sm ring-1 ring-slate-300 placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500 sm:text-sm bg-white"
                                            type="text"
                                            placeholder="Nama Jam Kerja"
                                            value={data.nama_jamkerja}
                                            onChange={(e) =>
                                                setData(
                                                    "nama_jamkerja",
                                                    e.target.value
                                                )
                                            }
                                        />
                                    </div>
                                    {errors.nama_jamkerja && (
                                        <p className="text-red-500 text-sm ml-40">
                                            {errors.nama_jamkerja}
                                        </p>
                                    )}

                                    {/* Awal Jam Masuk */}
                                    <div className="flex items-center">
                                        <label className="w-40 font-medium">
                                            Awal Jam Masuk:
                                        </label>
                                        <input
                                            className="block w-full rounded-md border-0 p-2 text-slate-900 shadow-sm ring-1 ring-slate-300 placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500 sm:text-sm bg-white"
                                            type="time"
                                            placeholder="Awal jam masuk kerja"
                                            value={data.awal_jam_masuk}
                                            onChange={(e) =>
                                                setData(
                                                    "awal_jam_masuk",
                                                    e.target.value
                                                )
                                            }
                                        />
                                    </div>
                                    {errors.awal_jam_masuk && (
                                        <p className="text-red-500 text-sm ml-40">
                                            {errors.awal_jam_masuk}
                                        </p>
                                    )}

                                    {/* Jam Masuk */}
                                    <div className="flex items-center">
                                        <label className="w-40 font-medium">
                                            Jam Masuk:
                                        </label>
                                        <input
                                            className="block w-full rounded-md border-0 p-2 text-slate-900 shadow-sm ring-1 ring-slate-300 placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500 sm:text-sm bg-white"
                                            type="time"
                                            placeholder="Jam Masuk Kerja"
                                            value={data.jam_masuk}
                                            onChange={(e) =>
                                                setData(
                                                    "jam_masuk",
                                                    e.target.value
                                                )
                                            }
                                        />
                                    </div>
                                    {errors.jam_masuk && (
                                        <p className="text-red-500 text-sm ml-40">
                                            {errors.jam_masuk}
                                        </p>
                                    )}

                                    {/* Jam Pulang */}
                                    <div className="flex items-center">
                                        <label className="w-40 font-medium">
                                            Jam Pulang:
                                        </label>
                                        <input
                                            className="block w-full rounded-md border-0 p-2 text-slate-900 shadow-sm ring-1 ring-slate-300 placeholder:text-slate-500 focus:ring-2 focus:ring-blue-500 sm:text-sm bg-white"
                                            type="time"
                                            placeholder="Jam Pulang"
                                            value={data.jam_pulang}
                                            onChange={(e) =>
                                                setData(
                                                    "jam_pulang",
                                                    e.target.value
                                                )
                                            }
                                        />
                                    </div>
                                    {errors.jam_pulang && (
                                        <p className="text-red-500 text-sm ml-40">
                                            {errors.jam_pulang}
                                        </p>
                                    )}

                                    {/* Tombol Simpan */}
                                    <div className="flex justify-center mt-6">
                                        <button
                                            className="bg-blue-900 text-white px-6 py-2 rounded-lg hover:bg-blue-950 mr-2"
                                            disabled={processing}
                                        >
                                            Simpan
                                        </button>
                                        <button
                                            onClick={handleDestroy}
                                            className="bg-red-600 text-white px-6 py-2 rounded-lg  hover:bg-red-700"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </>
                }
            />
        </>
    );
}
