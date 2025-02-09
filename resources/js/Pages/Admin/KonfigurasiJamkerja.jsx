import Modal from "@/Components/Modal";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link, useForm } from "@inertiajs/react";
import { useState } from "react";
import { FaEdit, FaPlus } from "react-icons/fa";
import { toast } from "sonner";

export default function KonfigurasiJamkerja({ jadwalShift }) {
    // modal input data shift baru
    const [showModal, setShowModal] = useState(false);
    const openModal = () => setShowModal(true);
    const closeModal = () => setShowModal(false);

    const { data, setData, post, errors, processing } = useForm({
        id: "",
        kode_jamkerja: "",
        nama_jamkerja: "",
        awal_jam_masuk: "",
        jam_masuk: "",
        jam_pulang: "",
    });

    function handleSubmit(e) {
        e.preventDefault();
        post(route("konfigurasi.store"), {
            onSuccess: () => {
                setData({
                    kode_jamkerja: "",
                    nama_jamkerja: "",
                    awal_jam_masuk: "",
                    jam_masuk: "",
                    akhir_jam_masuk: "",
                    jam_pulang: "",
                });
                toast.success("Shift kerja berhasil di tambahkan");
                closeModal();
            },

            onError: () => {
                closeModal();
                toast.error("Kode Jam kerja sudah digunakan");
            },
        });
    }

    return (
        <>
            <AuthenticatedLayout
                header={<>Konfigurasi jam kerja</>}
                children={
                    <>
                        <Head title="Konfigurasi Jam kerja" />
                        <div className="p-6 bg-white shadow-md rounded-lg">
                            <button
                                className="bg-blue-950 px-4 py-2 text-sm flex text-white rounded-md ml-14"
                                onClick={openModal}
                            >
                                <FaPlus className="my-auto mr-2" />
                                Tambah Data
                            </button>
                            <div className="max-w-screen-xl mx-auto p-6 rounded-md ">
                                <table>
                                    <thead>
                                        <tr>
                                            <th className="px-8 py-2 border border-gray-300">
                                                No
                                            </th>
                                            <th className="px-8 py-2 border border-gray-300">
                                                Kode Jam Kerja
                                            </th>
                                            <th className="px-8 py-2 border border-gray-300">
                                                Nama Jam Kerja
                                            </th>
                                            <th className="px-8 py-2 border border-gray-300">
                                                Awal Jam Masuk
                                            </th>
                                            <th className="px-8 py-2 border border-gray-300">
                                                Jam Masuk
                                            </th>
                                            <th className="px-8 py-2 border border-gray-300">
                                                Jam Pulang
                                            </th>
                                            <th className="px-8 py-2 border border-gray-300">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {jadwalShift.map((shft, index) => (
                                            <tr
                                                key={shft.kode_jamkerja}
                                                className="odd:bg-white even:bg-gray-100 hover:bg-gray-100"
                                            >
                                                <td className="px-8 py-2 border border-gray-300">
                                                    {index + 1}
                                                </td>
                                                <td className="px-8 py-2 border border-gray-300">
                                                    {shft.kode_jamkerja}
                                                </td>
                                                <td className="px-8 py-2 border border-gray-300">
                                                    {shft.nama_jamkerja}
                                                </td>
                                                <td className="px-8 py-2 border border-gray-300">
                                                    {shft.awal_jam_masuk}
                                                </td>
                                                <td className="px-8 py-2 border border-gray-300">
                                                    {shft.jam_masuk}
                                                </td>
                                                <td className="px-8 py-2 border border-gray-300">
                                                    {shft.jam_pulang}
                                                </td>
                                                <td className="px-8 py-2 border border-gray-300">
                                                    <Link
                                                        href={route(
                                                            "konfigurasi.edit",
                                                            shft
                                                        )}
                                                    >
                                                        <FaEdit className="hover:text-blue-500 w-5 h-5" />
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <Modal
                                show={showModal}
                                maxWidth="2xl"
                                closeable={true}
                                onClose={closeModal}
                            >
                                <div className="p-6">
                                    <p className="text-2xl font-bold text-center mb-10">
                                        Tambah Data Shift Pegawai
                                    </p>
                                    <form
                                        onSubmit={handleSubmit}
                                        encType="multipart/form-data"
                                    >
                                        <div className="space-y-4">
                                            {/* Kode Jam Kerja */}
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
                                                    className="bg-blue-900 text-white px-6 py-2 rounded-lg hover:bg-blue-950"
                                                    disabled={processing}
                                                >
                                                    Simpan
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </Modal>
                        </div>
                    </>
                }
            />
        </>
    );
}
