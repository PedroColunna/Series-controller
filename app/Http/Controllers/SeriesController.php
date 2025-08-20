<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeriesFormRequest;
use App\Jobs\DeleteSeriesCover;
use App\Models\Series;
use App\Repositories\SeriesRepository;
use Illuminate\Http\Request;
use App\Events\SeriesCreated;

class SeriesController extends Controller
{
    private SeriesRepository $repository;

    public function __construct(SeriesRepository $repository)
    {
        $this->repository = $repository;
        $this->middleware('auth')->except('index', 'show');
    }

    public function index()
    {
        try {
            $series = $this->repository->all();
            return view('series.index', compact('series'));
        } catch (\Exception $e) {
            return back()->with('mensagem.erro', 'Erro ao carregar séries: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('series.create');
    }

    public function store(SeriesFormRequest $request)
    {
        try {
            $coverPath = null;
            
            if ($request->hasFile('cover') && $request->file('cover')->isValid()) {
                $coverPath = $request->file('cover')->store('series_cover', 'public');
            }

            $data = $request->validated();
            $data['cover_path'] = $coverPath;

            $serie = $this->repository->add($data);

            SeriesCreated::dispatch(
                $serie->nome,
                $serie->id,
                $request->seasonsQty ?? 1,
                $request->episodesPerSeason ?? 1
            );

            return to_route('series.index')
                ->with('mensagem.sucesso', "Série '{$serie->nome}' adicionada com sucesso");

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('mensagem.erro', 'Erro ao criar série: ' . $e->getMessage());
        }
    }

    public function show(Series $series)
    {
        return view('series.show', compact('series'));
    }

    public function edit(Series $series)
    {
        return view('series.edit', compact('series'));
    }

    public function update(SeriesFormRequest $request, Series $series)
    {
        try {
            $data = $request->validated();
            
            if ($request->hasFile('cover') && $request->file('cover')->isValid()) {
                if ($series->cover_path) {
                    DeleteSeriesCover::dispatch($series->cover_path);
                }
                
                $data['cover_path'] = $request->file('cover')->store('series_cover', 'public');
            }

            $this->repository->update($series->id, $data);

            return to_route('series.index')
                ->with('mensagem.sucesso', "Série '{$series->nome}' atualizada com sucesso");

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('mensagem.erro', 'Erro ao atualizar série: ' . $e->getMessage());
        }
    }

    public function destroy(Series $series)
    {
        try {
            if ($series->cover_path) {
                DeleteSeriesCover::dispatch($series->cover_path);
            }

            $nomeSerie = $series->nome;
            $this->repository->delete($series->id);

            return to_route('series.index')
                ->with('mensagem.sucesso', "Série '{$nomeSerie}' removida com sucesso");

        } catch (\Exception $e) {
            return back()->with('mensagem.erro', 'Erro ao remover série: ' . $e->getMessage());
        }
    }
}